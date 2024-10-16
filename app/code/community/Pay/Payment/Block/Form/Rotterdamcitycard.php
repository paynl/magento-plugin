<?php

class Pay_Payment_Block_Form_Rotterdamcitycard extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Rotterdamcitycard::OPTION_ID;
    protected $paymentMethodName = 'Rotterdam Citycard';
    protected $methodCode = 'pay_payment_rotterdamcitycard';
    protected $template = 'pay/payment/form/default.phtml';
}
