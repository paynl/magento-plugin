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

}
    