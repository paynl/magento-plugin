<?php

class Pay_Payment_Block_Form_Overboeking extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Overboeking::OPTION_ID;
    protected $paymentMethodName = 'Overboeking';

    protected $methodCode = 'pay_payment_overboeking';

}
