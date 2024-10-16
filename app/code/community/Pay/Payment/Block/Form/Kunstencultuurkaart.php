<?php

class Pay_Payment_Block_Form_Kunstencultuurkaart extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Kunstencultuurkaart::OPTION_ID;
    protected $paymentMethodName = 'Kunst & Cultuur Kaart';
    protected $methodCode = 'pay_payment_kunstencultuurkaart';
    protected $template = 'pay/payment/form/default.phtml';
}
