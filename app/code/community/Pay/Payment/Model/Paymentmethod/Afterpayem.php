<?php
class Pay_Payment_Model_Paymentmethod_Afterpayem extends Pay_Payment_Model_Paymentmethod {
    const OPTION_ID = 740;
    protected $_paymentOptionId = 740;
    protected $_code = 'pay_payment_afterpayem';
    protected $_formBlockType = 'pay_payment/form_afterpayem';

    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if(!$this->addressEqual($quote)){
            return false;
        }

        return parent::isApplicableToQuote($quote, $checksBitMask);
    }
}
    