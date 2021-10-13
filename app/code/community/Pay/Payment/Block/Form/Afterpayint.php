<?php

class Pay_Payment_Block_Form_Afterpayint extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Afterpayint::OPTION_ID;
    protected $paymentMethodName = 'Afterpay International';
    protected $methodCode = 'pay_payment_afterpayint';

    protected $template = 'pay/payment/form/afterpayint.phtml';

}
