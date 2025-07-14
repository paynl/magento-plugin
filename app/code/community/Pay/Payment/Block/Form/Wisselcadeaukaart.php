<?php

class Pay_Payment_Block_Form_Wisselcadeaukaart extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Wisselcadeaukaart::OPTION_ID;
    protected $paymentMethodName = 'Wissel cadeaukaart';
    protected $methodCode = 'pay_payment_wisselcadeaukaart';
    protected $template = 'pay/payment/form/default.phtml';
}
