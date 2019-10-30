<?php

class Pay_Payment_Block_Form_Klarnakp extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Klarnakp::OPTION_ID;
    protected $paymentMethodName = 'Klarna KP';

    protected $methodCode = 'pay_payment_klarnakp';
}
