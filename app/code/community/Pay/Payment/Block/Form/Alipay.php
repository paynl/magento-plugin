<?php

class Pay_Payment_Block_Form_Alipay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Alipay::OPTION_ID;
    protected $paymentMethodName = 'AliPay';
    protected $methodCode = 'pay_payment_alipay';

}
