<?php
class Pay_Payment_Model_Paymentmethod_Spraypay extends Pay_Payment_Model_Paymentmethod {
    const OPTION_ID = 1987;
    protected $_paymentOptionId = 1987;
    protected $_code = 'pay_payment_spraypay';
    protected $_formBlockType = 'pay_payment/form_spraypay';

    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if(!$this->addressEqual($quote)){
            return false;
        }

        return parent::isApplicableToQuote($quote, $checksBitMask);
    }
}
    