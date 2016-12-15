<?php

class Pay_Payment_Block_Form_Afterpay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Afterpay::OPTION_ID;
    protected $paymentMethodName = 'Afterpay';
    protected $methodCode = 'pay_payment_afterpay';

}
