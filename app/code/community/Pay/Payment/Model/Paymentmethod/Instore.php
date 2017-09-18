<?php

class Pay_Payment_Model_Paymentmethod_Instore extends Pay_Payment_Model_Paymentmethod {
	const OPTION_ID = 1729;
	const CODE = 'pay_payment_instore';
	private static $_redirectUrl = null;
	protected $_paymentOptionId = 1729;
	protected $_code = 'pay_payment_instore';

	// Can only be used in backend orders
	protected $_formBlockType = 'pay_payment/form_instore';
	protected $_canUseInternal = true;
	protected $_canUseCheckout = true;

	public function __construct() {
		$this->setActiveCheckout();

		parent::__construct();
	}

	private function setActiveCheckout() {
		$this->_canUseCheckout = Mage::getStoreConfig( 'payment/pay_payment_instore/active_checkout' ) == 1;

		if ( Mage::getStoreConfig( 'payment/pay_payment_instore/active_checkout' ) == 2 ) {
			$ips      = Mage::getStoreConfig( 'payment/pay_payment_instore/active_checkout_ip' );
			$arrIps   = explode( ',', $ips );
			$arrIps   = array_map( 'trim', $arrIps );
			$clientIp = \Paynl\Helper::getIp();
			if ( in_array( $clientIp, $arrIps ) ) {
				$this->_canUseCheckout = true;
			}
		}
	}

	public static function startMultiPayment( Varien_Event_Observer $data ) {
		$method              = $data->getMethod();
		if ( $method == 'pay_payment_instore' ) {
			/**
			 * @var Mage_Sales_Model_Order $order
			 */
			$order = $data->getOrder();
			$amount = $data->getAmount();

			$methodData = $data->getMethodData();
			$terminalId = $methodData['additional_data']['terminalId'];
			$order->getPayment()->setAdditionalInformation( 'terminalId' , $terminalId);

			static::startPayment($order, $amount);
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

		return $payment->getRedirectUrl();
	}

	public function initialize( $paymentAction, $stateObject ) {
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

		return parent::initialize( $paymentAction, $stateObject );
	}

	public function getOrderPlaceRedirectUrl() {
		return self::$_redirectUrl;
	}

	public static function startPayment( Mage_Sales_Model_Order $order, $transaction_amount = null ) {
		/**
		 * @var $payment Mage_Sales_Model_Order_Payment
		 */
		$payment            = $order->getPayment();

		$transaction_amount = $payment->getAdditionalInformation( 'amount' );
		if ( empty( $transaction_amount ) ) {
			$transaction_amount = null;

			$method_data = $payment->getAdditionalInformation( 'method_data' );

			foreach ( $method_data as $method_row ) {
				if ( $method_row['code'] == self::CODE ) {
					if ( isset( $method_row['amount'] ) ) {
						$transaction_amount = $method_row['amount'];
					}
				}
			}

			$payment->setAdditionalInformation('method_data', $method_data);
		}


		$result = parent::startPayment( $order, $transaction_amount );

		$store       = $order->getStore();
		$pageSuccess = $store->getConfig( 'pay_payment/general/page_success' );
		$session     = Mage::getSingleton( 'checkout/session' );
		$sessionData = $session->getPaynlPaymentData();

		$terminalId = $order->getPayment()->getAdditionalInformation( 'terminalId' );
		if ( empty( $terminalId ) ) {
			if ( isset( $_POST['payment']['terminalId'] ) ) {
				$terminalId = $_POST['payment']['terminalId'];
			} elseif ( isset( $sessionData['terminalId'] ) ) {
				$terminalId = $sessionData['terminalId'];
			}
		}

		if ( $sendToTerminalResult = self::sendToTerminal( $result['transactionId'], $terminalId, $order ) ) {
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
    