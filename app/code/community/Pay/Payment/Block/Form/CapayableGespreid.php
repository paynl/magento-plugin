<?php

class Pay_Payment_Block_Form_CapayableGespreid extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_CapayableGespreid::OPTION_ID;
    protected $paymentMethodName = 'in3 keer betalen, 0% rente';

    protected $methodCode = 'pay_payment_capayablegespreid';

}
