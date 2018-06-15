<?php

class Pay_Payment_Block_Form_Wechatpay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Wechatpay::OPTION_ID;
    protected $paymentMethodName = 'Wechat Pay';

    protected $methodCode = 'pay_payment_wechatpay';

}
