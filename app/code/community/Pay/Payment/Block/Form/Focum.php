<?php

class Pay_Payment_Block_Form_Focum extends Pay_Payment_Block_Form_Abstract
{

    protected $paymentMethodId = Pay_Payment_Model_Paymentmethod_Focum::OPTION_ID;
    protected $paymentMethodName = 'Focum (AchterafBetalen.nl)';

    protected function _construct()
    {

        $showIcons = Mage::getStoreConfig('pay_payment/general/show_icons', Mage::app()->getStore());
        $iconSize = Mage::getStoreConfig('pay_payment/general/icon_size', Mage::app()->getStore());

        if (strpos($iconSize, 'x') === false) {
            $iconSize = $iconSize . 'x' . $iconSize;
        }

        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('pay/payment/mark.phtml')
            ->setPaymentMethodImageSrc('https://www.pay.nl/images/payment_profiles/' . $iconSize . '/' . $this->paymentMethodId . '.png')
            ->setPaymentMethodName($this->paymentMethodName);


        $template = $this->setTemplate('pay/payment/form/focum.phtml');


        if ($showIcons) {
            $template->setMethodLabelAfterHtml($mark->toHtml());
        }
        return $this;
//        return parent::_construct();
    }

}
