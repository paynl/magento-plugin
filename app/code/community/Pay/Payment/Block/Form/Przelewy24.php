<?php

class Pay_Payment_Block_Form_Przelewy24 extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Przelewy24::OPTION_ID;
    protected $paymentMethodName = 'Przelewy24';

    protected $methodCode = 'pay_payment_przelewy24';
}
