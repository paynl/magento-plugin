<?php

class Pay_Payment_Block_Form_Mistercash extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Mistercash::OPTION_ID;
    protected $paymentMethodName = 'Bancontact/Mistercash';

    protected $methodCode = 'pay_payment_mistercash';

}
