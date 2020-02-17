<?php

class Pay_Payment_Block_Form_Multibanco extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Multibanco::OPTION_ID;
    protected $paymentMethodName = 'Multibanco';

    protected $methodCode = 'pay_payment_multibanco';
}
