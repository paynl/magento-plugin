<?php

class Pay_Payment_Model_Paymentmethod_Paylink extends Pay_Payment_Model_Paymentmethod
{

    const OPTION_ID = 961;
    protected $_paymentOptionId = 961;
    protected $_code = 'pay_payment_paylink';
    protected $_formBlockType = 'pay_payment/form_paylink';
    // Can only be used in backend orders
    protected $_canUseInternal = true;
    protected $_canUseCheckout = false;



    public function initialize($paymentAction, $stateObject)
    {
        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE:
            case self::ACTION_AUTHORIZE_CAPTURE:
                $payment = $this->getInfoInstance();
                $order = $payment->getOrder();
                $method = $payment->getMethodInstance();

                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'pending_payment', '', false);

                $data = $method->startPayment($order);

                $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
                $stateObject->setStatus('pending_payment');
                $stateObject->setIsNotified(false);
                break;
            default:
                break;
        }
        return parent::initialize($paymentAction, $stateObject);
    }
}
    