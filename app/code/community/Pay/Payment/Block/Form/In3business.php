<?php

class Pay_Payment_Block_Form_In3business extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_In3business::OPTION_ID;
    protected $paymentMethodName = 'Mondu';
    protected $methodCode = 'pay_payment_in3business';
    protected $template = 'pay/payment/form/default.phtml';
}
