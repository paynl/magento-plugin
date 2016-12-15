<?php

class Pay_Payment_Block_Form_Telefoon extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Telefoon::OPTION_ID;
    protected $paymentMethodName = 'Telefoon';

    protected $methodCode = 'pay_payment_telefoon';

}
