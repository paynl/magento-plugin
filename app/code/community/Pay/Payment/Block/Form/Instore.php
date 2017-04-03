<?php

class Pay_Payment_Block_Form_Instore extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Instore::OPTION_ID;
    protected $paymentMethodName = 'Instore';

    protected $methodCode = 'pay_payment_instore';

    protected $template = 'pay/payment/form/instore.phtml';

    protected function getTerminals() {
        $helper = Mage::helper('pay_payment');
        $option = $helper->getOption(Pay_Payment_Model_Paymentmethod_Instore::OPTION_ID);
        $banks = $option->getSubs();
        return $banks;
    }
}
