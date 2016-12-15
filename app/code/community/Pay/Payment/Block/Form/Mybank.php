<?php

class Pay_Payment_Block_Form_Mybank extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Mybank::OPTION_ID;
    protected $paymentMethodName = 'Mybank';

    protected $methodCode = 'pay_payment_mybank';

}
