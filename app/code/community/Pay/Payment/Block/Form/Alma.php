<?php

class Pay_Payment_Block_Form_Alma extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Alma::OPTION_ID;
    protected $paymentMethodName = 'Alma';
    protected $methodCode = 'pay_payment_alma';
    protected $template = 'pay/payment/form/default.phtml';
}
