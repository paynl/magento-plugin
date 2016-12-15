<?php

class Pay_Payment_Block_Form_Paypal extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Paypal::OPTION_ID;
    protected $paymentMethodName = 'Paypal';

    protected $methodCode = 'pay_payment_paypal';

}
