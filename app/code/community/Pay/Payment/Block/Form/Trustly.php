<?php

class Pay_Payment_Block_Form_Trustly extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Trustly::OPTION_ID;
    protected $paymentMethodName = 'Trustly';

    protected $methodCode = 'pay_payment_trustly';

}
