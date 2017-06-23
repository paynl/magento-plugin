<?php
class Pay_Payment_Model_Paymentmethod_Afterpay extends Pay_Payment_Model_Paymentmethod {
    const OPTION_ID = 739;
    protected $_paymentOptionId = 739;
    protected $_code = 'pay_payment_afterpay';
    protected $_formBlockType = 'pay_payment/form_afterpay';

    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if(!$this->addressEqual($quote)){
            return false;
        }

        return parent::isApplicableToQuote($quote, $checksBitMask);
    }

}
    