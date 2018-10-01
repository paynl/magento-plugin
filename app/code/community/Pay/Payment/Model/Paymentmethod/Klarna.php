<?php

class Pay_Payment_Model_Paymentmethod_Klarna extends Pay_Payment_Model_Paymentmethod
{
    const OPTION_ID = 1717;
    protected $_paymentOptionId = 1717;
    protected $_code = 'pay_payment_klarna';
    protected $_formBlockType = 'pay_payment/form_klarna';

    /**
     * @return boolean
     */
    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if(!$this->addressEqual($quote)){
            return false;
        }
        if ($quote->getShippingAddress()->getCompany()) {
            return false;
        }
        if ($quote->getBillingAddress()->getCompany()) {
            return false;
        }

        return parent::isApplicableToQuote($quote, $checksBitMask);
    }

	protected static function getFirstname($address){
		return $address->getFirstname();
	}

    /**
     * Firstname lastname and country must be equal
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    protected static function addressEqual(Mage_Sales_Model_Quote $quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();


        if (strtolower($billingAddress->getFirstname()) !== strtolower($shippingAddress->getFirstname())) {
            return false;
        }

        if (strtolower($billingAddress->getLastname()) !== strtolower($shippingAddress->getLastname())) {
            return false;
        }

        if (strtolower($billingAddress->getCountryId()) !== strtolower($shippingAddress->getCountryId())) {
            return false;
        }

        return true;
    }
}
    