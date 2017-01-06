<?php

class Pay_Payment_Block_Form_Paylink extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Paylink::OPTION_ID;
    protected $paymentMethodName = 'Paylink';

    protected $methodCode = 'pay_payment_paylink';

    protected $template = 'pay/payment/form/paylink.phtml';

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate($this->template);
    }
}
