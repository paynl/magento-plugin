<?php

class Pay_Payment_Block_Form_Kidsorteen extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Kidsorteen::OPTION_ID;
    protected $paymentMethodName = 'Kids or teen';
    protected $methodCode = 'pay_payment_kidsorteen';
    protected $template = 'pay/payment/form/default.phtml';
}
