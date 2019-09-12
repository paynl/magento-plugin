<?php

class Pay_Payment_Block_Form_Applepay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Applepay::OPTION_ID;
    protected $paymentMethodName = 'Apple Pay';
    protected $methodCode = 'pay_payment_applepay';

}
