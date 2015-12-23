<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Pay_Payment_Block_Form_Ideal extends Mage_Payment_Block_Form {

    /**
     * Constructor. Set template.
     */
    protected function _construct() {
       
        $bankSelectType = Mage::getStoreConfig('payment/pay_payment_ideal/bank_select_type', Mage::app()->getStore());
        $showIcons = Mage::getStoreConfig('pay_payment/general/show_icons', Mage::app()->getStore());
        $iconSize = Mage::getStoreConfig('pay_payment/general/icon_size', Mage::app()->getStore());
        if(strpos($iconSize, 'x') === false){
            $iconSize = $iconSize.'x'.$iconSize;
        }
        
        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('pay/payment/mark.phtml')
                ->setPaymentMethodImageSrc('https://www.pay.nl/images/payment_profiles/'.$iconSize.'/10.png')
                ->setPaymentMethodName('iDEAL');
        
        
        if ($bankSelectType == 'radio') {
            $template = $this->setTemplate('pay/payment/form/ideal.phtml');
        } elseif($bankSelectType == 'select') {
            $template = $this->setTemplate('pay/payment/form/idealSelect.phtml');
        } else {
            $template = $this->setTemplate('pay/payment/form/default.phtml');
        }
        if($showIcons){
            $template->setMethodLabelAfterHtml($mark->toHtml());
        }
        return  parent::_construct();
    }

    protected function getBanks() {
        $helper = Mage::helper('pay_payment');
        $option = $helper->getOption(Pay_Payment_Model_PaymentMethod_Ideal::OPTION_ID);
        $banks = $option->getSubs();
        return $banks;
    }

}
