<?php

class Pay_Payment_Block_Form_Eps extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Eps::OPTION_ID;
    protected $paymentMethodName = 'Eps-Überweisung';

    protected $methodCode = 'pay_payment_eps';
}
