<?php

class Pay_Payment_Block_Form_Alipayplus extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Alipayplus::OPTION_ID;
    protected $paymentMethodName = 'AliPay Plus';
    protected $methodCode = 'pay_payment_alipayplus';
    protected $template = 'pay/payment/form/default.phtml';
}
