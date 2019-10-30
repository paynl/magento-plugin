<?php

class Pay_Payment_Block_Form_Abstract extends Mage_Payment_Block_Form
{

    protected $paymentMethodId = 0;
    protected $paymentMethodName = '';
    protected $methodCode = '';

    protected $template = 'pay/payment/form/default.phtml';

    /**
     * @var $quote Mage_Sales_Model_Quote
     */
    protected $quote;



    protected function _construct()
    {
        parent::_construct();
        /* @var $session Mage_Checkout_Model_Session */
        $session = Mage::getSingleton('checkout/session');
        $this->quote = $session->getQuote();


        $markClass = Mage::getConfig()->getBlockClassName('core/template');
        $showIcons = Mage::getStoreConfig('pay_payment/general/show_icons', Mage::app()->getStore());
        $iconSize = Mage::getStoreConfig('pay_payment/general/icon_size', Mage::app()->getStore());

        $showFee = Mage::getStoreConfig('pay_payment/general/show_fee', Mage::app()->getStore());

        if (strpos($iconSize, 'x') === false) {
            $iconSize = $iconSize . 'x' . $iconSize;
        }

        $mark = new $markClass;
        $mark->setTemplate('pay/payment/mark.phtml')
            ->setPaymentMethodImageSrc('https://www.static.pay.nl/payment_profiles/' . $iconSize . '/' . $this->paymentMethodId . '.png')
            ->setPaymentMethodName($this->paymentMethodName);

        $fee = $this->getPaymentCharge();

        $template = $this->setTemplate($this->template);

        $addHtml = '';

        if ($showIcons) {
            $addHtml = $mark->toHtml();
        }

        if ($showFee > 0) {
            if ($fee > 0 || $showFee == 2) {
                $currencyCode = $this->quote->getQuoteCurrencyCode();
                $currencySymbol = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();
                $addHtml .= "<span class='price'>$currencySymbol " . number_format($fee, 2, ',', '.') . '</span>';
            }
        }

        $template->setMethodLabelAfterHtml($addHtml);

    }

    protected function getPaymentCharge()
    {
        $baseAmount = Mage::helper('pay_payment')->getPaymentCharge($this->methodCode, $this->quote);
        $amount = Mage::helper('directory')->currencyConvert($baseAmount, Mage::app()->getWebsite()->getConfig('currency/options/default'), Mage::app()->getStore()->getCurrentCurrencyCode());

        return $amount;
    }

    protected function getQuote(){
        return $this->quote;
    }

    protected function getIban()
    {
        $session = Mage::getSingleton('checkout/session');
        $additionalData = $session->getPaynlPaymentData();
        if(isset($additionalData['iban'])){
            return $additionalData['iban'];
        }
        return '';
    }

    protected function getDob()
    {
        $session = Mage::getSingleton('checkout/session');

        $additionalData = $session->getPaynlPaymentData();

        if (
            isset($additionalData['birthday_day']) &&
            isset($additionalData['birthday_month']) &&
            isset($additionalData['birthday_year'])
        ) {
            return $additionalData['birthday_year'] . '-' . $additionalData['birthday_month'] . '-' . $additionalData['birthday_day'];
        }

        list($dob) = explode(' ', $this->quote->getCustomerDob());


        return $dob;
    }
    protected function getInstructions(){
        return $this->getMethod()->getConfigData('instructions');
    }


}
