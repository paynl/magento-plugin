<?php

class Pay_Payment_Block_Form_Postepay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Postepay::OPTION_ID;
    protected $paymentMethodName = 'PostePay';

    protected $methodCode = 'pay_payment_postepay';
}
