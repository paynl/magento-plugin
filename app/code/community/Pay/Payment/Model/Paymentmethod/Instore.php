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

    private function sendToTerminal($transactionId, $terminalId)
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

        $result = parent::startPayment($order);

        $store = $order->getStore();
        $pageSuccess = $store->getConfig('pay_payment/general/page_success');

        if($this->sendToTerminal($result['transactionId'], $_POST['payment']['terminalId'])){
            $result['url'] = $pageSuccess;
            $this->_redirectUrl = $pageSuccess;
            return $result;
        } else{
            Mage::throwException('Payment canceled');
        }

    }
}
    