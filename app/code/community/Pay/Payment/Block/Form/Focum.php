<?php

class Pay_Payment_Block_Form_Focum extends Pay_Payment_Block_Form_Abstract
{
    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Focum::OPTION_ID;
    protected $paymentMethodName = 'Focum (AchterafBetalen.nl)';

    protected $methodCode = 'pay_payment_focum';

    protected $template = 'pay/payment/form/focum.phtml';
}
