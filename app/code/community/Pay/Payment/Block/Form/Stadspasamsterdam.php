<?php

class Pay_Payment_Block_Form_Stadspasamsterdam extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Stadspasamsterdam::OPTION_ID;
    protected $paymentMethodName = 'Stadspas Amsterdam';
    protected $methodCode = 'pay_payment_stadspasamsterdam';
    protected $template = 'pay/payment/form/default.phtml';
}
