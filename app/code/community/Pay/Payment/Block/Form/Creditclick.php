<?php

class Pay_Payment_Block_Form_Creditclick extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Creditclick::OPTION_ID;
    protected $paymentMethodName = 'CreditClick';

    protected $methodCode = 'pay_payment_creditclick';
}
