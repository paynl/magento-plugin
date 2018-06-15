<?php

class Pay_Payment_Block_Form_Cashly extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Cashly::OPTION_ID;
    protected $paymentMethodName = 'Cashly';

    protected $methodCode = 'pay_payment_cashly';
}
