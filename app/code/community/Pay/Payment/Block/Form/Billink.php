<?php

class Pay_Payment_Block_Form_Billink extends Mage_Payment_Block_Form {

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Billink::OPTION_ID;
    protected $paymentMethodName = 'Billink';

    protected function _construct() {

//        $bankSelectType = Mage::getStoreConfig('payment/pay_payment_ideal/bank_select_type', Mage::app()->getStore());
        $showIcons = Mage::getStoreConfig('pay_payment/general/show_icons', Mage::app()->getStore());
        $iconSize = Mage::getStoreConfig('pay_payment/general/icon_size', Mage::app()->getStore());

        $enablePersonal = Mage::getStoreConfig('payment/pay_payment_billink/ask_data_personal', Mage::app()->getStore());
        $enableBusiness = Mage::getStoreConfig('payment/pay_payment_billink/ask_data_business', Mage::app()->getStore());

        if(strpos($iconSize, 'x') === false){
            $iconSize = $iconSize.'x'.$iconSize;
        }

        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('pay/payment/mark.phtml')
                ->setPaymentMethodImageSrc('https://www.pay.nl/images/payment_profiles/'.$iconSize.'/'.$this->paymentMethodId.'.png')
                ->setPaymentMethodName('Billink');

        if($enablePersonal == 1 || $enableBusiness == 1){
            $template = $this->setTemplate('pay/payment/form/billink.phtml');
        } else {
            $template = $this->setTemplate('pay/payment/form/default.phtml');
        }

        if($showIcons){
            $template->setMethodLabelAfterHtml($mark->toHtml());
        }
        return  parent::_construct();
    }

}
