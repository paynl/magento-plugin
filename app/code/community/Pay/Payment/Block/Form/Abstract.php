<?php

class Pay_Payment_Block_Form_Abstract extends Mage_Payment_Block_Form {

    protected $paymentMethodId = 0;
    protected $paymentMethodName = '';

    protected function _construct() {     
        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $showIcons = Mage::getStoreConfig('pay_payment/general/show_icons', Mage::app()->getStore());
        $iconSize = Mage::getStoreConfig('pay_payment/general/icon_size', Mage::app()->getStore());

        if(strpos($iconSize, 'x') === false){
            $iconSize = $iconSize.'x'.$iconSize;
        }

        $mark = new $mark;
        $mark->setTemplate('pay/payment/mark.phtml')
                ->setPaymentMethodImageSrc('https://www.pay.nl/images/payment_profiles/'.$iconSize.'/' . $this->paymentMethodId . '.png')
                ->setPaymentMethodName($this->paymentMethodName);
        

        $template = $this->setTemplate('pay/payment/form/default.phtml');
        if($showIcons){
            $template->setMethodLabelAfterHtml($mark->toHtml());
        }
        
        return parent::_construct();
    }

}
