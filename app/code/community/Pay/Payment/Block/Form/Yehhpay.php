<?php

class Pay_Payment_Block_Form_Yehhpay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Yehhpay::OPTION_ID;
    protected $paymentMethodName = 'Yehhpay';
    protected $methodCode = 'pay_payment_yehhpay';

    protected $template = 'pay/payment/form/yehhpay.phtml';

}
