<?php

class Pay_Payment_Block_Form_Billink extends Pay_Payment_Block_Form_Abstract {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Billink::OPTION_ID;
    protected $paymentMethodName = 'Billink';
    protected $methodCode = 'pay_payment_billink';

    protected $template = 'pay/payment/form/default.phtml';

    protected function _construct() {
        $enablePersonal = Mage::getStoreConfig('payment/pay_payment_billink/ask_data_personal', Mage::app()->getStore());
        $enableBusiness = Mage::getStoreConfig('payment/pay_payment_billink/ask_data_business', Mage::app()->getStore());

        if($enablePersonal == 1 || $enableBusiness == 1){
            $this->template ='pay/payment/form/billink.phtml';
        } else {
            $this->template = 'pay/payment/form/default.phtml';
        }

        return  parent::_construct();
    }

}
