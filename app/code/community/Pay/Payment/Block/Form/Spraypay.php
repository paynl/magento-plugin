<?php

class Pay_Payment_Block_Form_Spraypay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Spraypay::OPTION_ID;
    protected $paymentMethodName = 'SprayPay';

    protected $methodCode = 'pay_payment_spraypay';

}
