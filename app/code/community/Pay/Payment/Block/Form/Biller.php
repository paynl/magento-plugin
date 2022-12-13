<?php

class Pay_Payment_Block_Form_Biller extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Biller::OPTION_ID;
    protected $paymentMethodName = 'Biller';
    protected $template = 'pay/payment/form/default.phtml';
    protected $methodCode = 'pay_payment_biller';
}
