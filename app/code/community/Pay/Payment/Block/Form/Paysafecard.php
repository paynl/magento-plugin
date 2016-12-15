<?php

class Pay_Payment_Block_Form_Paysafecard extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Paysafecard::OPTION_ID;
    protected $paymentMethodName = 'Paysafecard';

    protected $methodCode = 'pay_payment_paysafecard';

}
