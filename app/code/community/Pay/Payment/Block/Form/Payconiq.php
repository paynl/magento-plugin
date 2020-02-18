<?php

class Pay_Payment_Block_Form_Payconiq extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Payconiq::OPTION_ID;
    protected $paymentMethodName = 'Payconiq';

    protected $methodCode = 'pay_payment_payconiq';
}
