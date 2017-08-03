<?php

class Pay_Payment_Model_Paymentmethod_Instore extends Pay_Payment_Model_Paymentmethod
{
    const OPTION_ID = 1729;
    protected $_paymentOptionId = 1729;
    protected $_code = 'pay_payment_instore';
    protected $_formBlockType = 'pay_payment/form_instore';

    // Can only be used in backend orders
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;

    private $_redirectUrl = null;

    public function __construct()
    {
        $this->_canUseCheckout = Mage::getStoreConfig('payment/pay_payment_instore/active_checkout')==1;

        parent::__construct();
    }

    public function initialize($paymentAction, $stateObject)
    {
        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE:
            case self::ACTION_AUTHORIZE_CAPTURE:
                $payment = $this->getInfoInstance();
                /** @var Mage_Sales_Model_Order $order */
                $order = $payment->getOrder();

                /** @var Pay_Payment_Model_Paymentmethod $method */
                $method = $payment->getMethodInstance();

                $this->_startResult = $method->startPayment($order);

                return true;
                break;
            default:
                break;
        }
        return parent::initialize($paymentAction, $stateObject);
    }

    private static function sendToTerminal($transactionId, $terminalId, $order)
    {
        $payment = \Paynl\Instore::payment(array(
            'transactionId' => $transactionId,
            'terminalId' => $terminalId
        ));

        $hash = $payment->getHash();

        for ($i = 0; $i < 60; $i++) {
            $status = \Paynl\Instore::status(array('hash' => $hash));
            $state = $status->getTransactionState();

            if ($state != 'init') {
                if ($state == 'approved') {

                    $receiptData = \Paynl\Instore::getReceipt(array('hash' => $hash));
                    $approvalId = $receiptData->getApprovalId();
                    $receipt = $receiptData->getReceipt();

                    $order->getPayment()->setAdditionalInformation('paynl_receipt', $receipt);
                    $order->getPayment()->setAdditionalInformation('paynl_transaction_id', $approvalId);

                    return true;
                }
                return false;
            }

            sleep(1);
        }
        return false;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->_redirectUrl;
    }

    public function startPayment(Mage_Sales_Model_Order $order)
    {
        if(!is_null($this->_startResult)){
            return $this->_startResult;
        }

        $transaction_amount = $order->getPayment()->getAdditionalInformation('amount');
        $transaction_amount = ($transaction_amount)?$transaction_amount:null;

        $result = parent::startPayment($order, $transaction_amount);

        $store = $order->getStore();
        $pageSuccess = $store->getConfig('pay_payment/general/page_success');

        $terminalId =  $order->getPayment()->getAdditionalInformation('terminalId');
        $terminalId = ($terminalId)?$terminalId:$_POST['payment']['terminalId'];
        if($this->sendToTerminal($result['transactionId'], $terminalId, $order)){
            $result['url'] = $pageSuccess;
            $this->_redirectUrl = $pageSuccess;
            return $result;
        } else{
            Mage::throwException('Payment canceled');
        }

    }
    public static function startMultiPayment(Varien_Event_Observer $data){
        $method = $data->getMethod();
        if($method == 'pay_payment_instore') {
            $amount = $data->getAmount();

            $methodData = $data->getMethodData();
            $terminalId = $methodData['additional_data']['terminalId'];

            /**
             * @var Mage_Sales_Model_Order $order
             */
            $order = $data->getOrder();
            $store = $order->getStore();
            $payHelper  = Mage::helper('pay_payment');
            $payHelper->loginSDK($store);

            $ipAddress = $order->getRemoteIp();
            if (empty($ipAddress)) $ipAddress = \Paynl\Helper::getIp();
            if (strpos($ipAddress, ',') !== false) {
                $ipAddress = substr($ipAddress, 0, strpos($ipAddress, ','));
            }

            $startData = array(
                'amount' => $amount,
                'returnUrl' => 'http://dummy_url.com',
                'ipaddress' => $ipAddress,

                'paymentMethod' => self::OPTION_ID,
                'description' => $order->getIncrementId(),
                'currency' => $order->getOrderCurrencyCode(),
                'extra1' => $order->getIncrementId(),
                'extra2' => $order->getCustomerEmail(),
                'ipAddress' => $ipAddress
            );

            $transaction = \Paynl\Transaction::start($startData);

            $terminalResult = self::sendToTerminal($transaction->getTransactionId(), $terminalId, $order);
            if(!$terminalResult){
                Mage::throwException('Payment canceled');
            }
        }
    }
}
    