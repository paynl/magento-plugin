<?php

class Pay_Payment_Block_Form_Googlepay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Googlepay::OPTION_ID;
    protected $paymentMethodName = 'Google Pay';
    protected $methodCode = 'pay_payment_googlepay';
    protected $template = 'pay/payment/form/google.phtml';
}
