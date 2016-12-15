<?php

class Pay_Payment_Block_Form_Capayable extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Capayable::OPTION_ID;
    protected $paymentMethodName = 'Capayable';

    protected $methodCode = 'pay_payment_capayable';
}
