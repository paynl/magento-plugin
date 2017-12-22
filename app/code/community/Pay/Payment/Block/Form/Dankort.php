<?php

class Pay_Payment_Block_Form_Dankort extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Dankort::OPTION_ID;
    protected $paymentMethodName = 'Dankort';

    protected $methodCode = 'pay_payment_dankort';
}
