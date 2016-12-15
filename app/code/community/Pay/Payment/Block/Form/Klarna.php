<?php

class Pay_Payment_Block_Form_Klarna extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Klarna::OPTION_ID;
    protected $paymentMethodName = 'Klarna';
    protected $methodCode = 'pay_payment_klarna';

    protected $template = 'pay/payment/form/klarna.phtml';
}
