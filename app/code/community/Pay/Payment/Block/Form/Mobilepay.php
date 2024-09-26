<?php

class Pay_Payment_Block_Form_Mobilepay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Mobilepay::OPTION_ID;
    protected $paymentMethodName = 'MobilePAY';
    protected $methodCode = 'pay_payment_mobilepay';
    protected $template = 'pay/payment/form/default.phtml';
}
