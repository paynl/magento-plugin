<?php

class Pay_Payment_Block_Form_Sofortbanking extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Sofortbanking::OPTION_ID;
    protected $paymentMethodName = 'Sofortbanking';

    protected $methodCode = 'pay_payment_sofortbanking';

}
