<?php

class Pay_Payment_Block_Form_Xafaxmynetpay extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Xafaxmynetpay::OPTION_ID;
    protected $paymentMethodName = 'Xafax Mynetpay';
    protected $methodCode = 'pay_payment_xafaxmynetpay';
    protected $template = 'pay/payment/form/default.phtml';
}
