<?php

class Pay_Payment_Block_Form_Maestro extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Maestro::OPTION_ID;
    protected $paymentMethodName = 'Maestro';

    protected $methodCode = 'pay_payment_maestro';
}
