<?php

class Pay_Payment_Block_Form_Fashioncheque extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Fashioncheque::OPTION_ID;
    protected $paymentMethodName = 'Fashioncheque';

    protected $methodCode = 'pay_payment_fashioncheque';

}
