<?php

class Pay_Payment_Model_Paymentmethod_Instore extends Pay_Payment_Model_Paymentmethod {
	const OPTION_ID = 1729;
	protected $_paymentOptionId = 1729;
	protected $_code = 'pay_payment_instore';
	protected $_formBlockType = 'pay_payment/form_instore';
//	protected $_isInitializeNeeded = false;

	protected static $_isAdminOrder = false;

	// Can only be used in backend orders
	protected $_canUseInternal = true;
	protected $_canUseCheckout = true;


	private static $_redirectUrl = null;

	private function setActiveCheckout(){
		$this->_canUseCheckout = Mage::getStoreConfig( 'payment/pay_payment_instore/active_checkout' ) == 1;

		if(Mage::getStoreConfig( 'payment/pay_payment_instore/active_checkout') == 2 ){
			$ips = Mage::getStoreConfig( 'payment/pay_payment_instore/active_checkout_ip');
			$arrIps = explode(',', $ips);
			$arrIps = array_map('trim', $arrIps);
			$clientIp = \Paynl\Helper::getIp();
			if(in_array($clientIp, $arrIps)){
				$this->_canUseCheckout = true;
			}
		}
	}

	public function __construct() {
		$this->setActiveCheckout();

		parent::__construct();
	}

	public static function startMultiPayment( Varien_Event_Observer $data ) {
		self::$_isAdminOrder = true;
		$method = $data->getMethod();
		if ( $method == 'pay_payment_instore' ) {
			$amount = $data->getAmount();

			$methodData = $data->getMethodData();
			$terminalId = $methodData['additional_data']['terminalId'];

			/**
			 * @var Mage_Sales_Model_Order $order
			 */
			$order = $data->getOrder();
			$store = $order->getStore();
			/**
			 * @var $payHelper Pay_Payment_Helper_Data
			 */
			$payHelper = Mage::helper( 'pay_payment' );
			$payHelper->loginSDK( $store );

			$ipAddress = $order->getRemoteIp();
			if ( empty( $ipAddress ) ) {
				$ipAddress = \Paynl\Helper::getIp();
			}
			if ( strpos( $ipAddress, ',' ) !== false ) {
				$ipAddress = substr( $ipAddress, 0, strpos( $ipAddress, ',' ) );
			}

			$startData = array(
				'amount'    => $amount,
				'returnUrl' => 'http://dummy_url.com',
				'ipaddress' => $ipAddress,

				'paymentMethod' => self::OPTION_ID,
				'description'   => $order->getIncrementId(),
				'currency'      => $order->getOrderCurrencyCode(),
				'extra1'        => $order->getIncrementId(),
				'extra2'        => $order->getCustomerEmail(),
				'ipAddress'     => $ipAddress
			);

			$transaction = \Paynl\Transaction::start( $startData );

			$terminalResult = self::sendToTerminal( $transaction->getTransactionId(), $terminalId, $order );
			if ( ! $terminalResult ) {
				Mage::throwException( 'Payment canceled' );
			}
		}
	}

	private static function sendToTerminal( $transactionId, $terminalId, $order ) {
		$payment = \Paynl\Instore::payment( array(
			'transactionId' => $transactionId,
			'terminalId'    => $terminalId
		) );

		$hash = $payment->getHash();
		/**
		 * @var $orderPayment Mage_Sales_Model_Order_Payment
		 */
		$orderPayment = $order->getPayment();

		$orderPayment->setAdditionalInformation( 'paynl_hash', $hash );
		$orderPayment->save();
		if ( ! self::$_isAdminOrder ) {
			return $payment->getRedirectUrl();
		}

		for ( $i = 0; $i < 60; $i ++ ) {
			$status = \Paynl\Instore::status( array( 'hash' => $hash ) );
			$state  = $status->getTransactionState();

			if ( $state != 'init' ) {
				if ( $state == 'approved' ) {

					$receiptData = \Paynl\Instore::getReceipt( array( 'hash' => $hash ) );
					$approvalId  = $receiptData->getApprovalId();
					$receipt     = $receiptData->getReceipt();

					$order->getPayment()->setAdditionalInformation( 'paynl_receipt', $receipt );
					$order->getPayment()->setAdditionalInformation( 'paynl_transaction_id', $approvalId );

					$order->save();

					return true;
				}

				return false;
			}

			sleep( 1 );
		}

		return false;
	}

	private static function checkAdminOrder(){
		if(Mage::app()->getStore()->isAdmin() || $_REQUEST['type'] == 'rest') {
			self::$_isAdminOrder = true;
		} else {
			self::$_isAdminOrder = false;
		}
	}

	public function initialize( $paymentAction, $stateObject ) {
		self::checkAdminOrder();

//		if ( self::$_isAdminOrder ) {
			switch ( $paymentAction ) {
				case self::ACTION_AUTHORIZE:
				case self::ACTION_AUTHORIZE_CAPTURE:
					$payment = $this->getInfoInstance();
					/** @var Mage_Sales_Model_Order $order */
					$order = $payment->getOrder();

					/** @var Pay_Payment_Model_Paymentmethod $method */
					$method = $payment->getMethodInstance();

					$this->_startResult = $method->startPayment( $order );

					return true;
					break;
				default:
					break;
			}
//		}

		return parent::initialize( $paymentAction, $stateObject );
	}

	public function getOrderPlaceRedirectUrl() {
		return self::$_redirectUrl;
	}

	public function startPayment( Mage_Sales_Model_Order $order, $transaction_amount = null ) {
		if ( ! is_null( $this->_startResult ) ) {
			return $this->_startResult;
		}
		$payment = $order->getPayment();
		$transaction_amount = $payment->getAdditionalInformation( 'amount' );
		if(empty($transaction_amount)){
			$transaction_amount = null;

			$method_data = $payment->getAdditionalInformation('method_data');

			foreach($method_data as $method_row){
				if($method_row['code'] == $this->_code){
					if(isset($method_row['amount'])){
						$transaction_amount = $method_row['amount'];
					}
				}
			}
		}

		$result = parent::startPayment( $order, $transaction_amount );

		$store       = $order->getStore();
		$pageSuccess = $store->getConfig( 'pay_payment/general/page_success' );
		$session = Mage::getSingleton('checkout/session');
		$sessionData = $session->getPaynlPaymentData();

		$terminalId = $order->getPayment()->getAdditionalInformation( 'terminalId' );
		if(empty($terminalId)){
			if(isset($_POST['payment']['terminalId'])){
				$terminalId = $_POST['payment']['terminalId'];
			} elseif(isset($sessionData['terminalId'])){
				$terminalId = $sessionData['terminalId'];
			}
		}

		if ( $sendToTerminalResult = $this->sendToTerminal( $result['transactionId'], $terminalId, $order ) ) {
			if ( is_string( $sendToTerminalResult ) ) {
				$pageSuccess = $sendToTerminalResult;
			}
			$result['url']      = $pageSuccess;
			self::$_redirectUrl = $pageSuccess;

			return $result;
		} else {
			Mage::throwException( 'Payment canceled' );
		}
	}
}
    