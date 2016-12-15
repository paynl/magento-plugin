<?php

class Pay_Payment_Block_Form_Visamastercard extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Visamastercard::OPTION_ID;
    protected $paymentMethodName = 'Visa/Mastercard';

    protected $methodCode = 'pay_payment_visamastercard';
}
