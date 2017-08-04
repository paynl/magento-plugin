<?php

class Pay_Payment_Block_Form_Amazonpay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Amazonpay::OPTION_ID;
    protected $paymentMethodName = 'Amazon Pay';
    protected $methodCode = 'pay_payment_amazonpay';

}
