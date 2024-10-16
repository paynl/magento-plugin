<?php

class Pay_Payment_Block_Form_Flyingblueplus extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Flyingblueplus::OPTION_ID;
    protected $paymentMethodName = 'Flying blue+';
    protected $methodCode = 'pay_payment_flyingblueplus';
    protected $template = 'pay/payment/form/default.phtml';
}
