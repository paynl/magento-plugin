<?php

class Pay_Payment_Block_Form_Cartasi extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Cartasi::OPTION_ID;
    protected $paymentMethodName = 'CartaSi';

    protected $methodCode = 'pay_payment_cartasi';
}
