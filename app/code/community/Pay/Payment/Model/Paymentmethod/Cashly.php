<?php
class Pay_Payment_Model_Paymentmethod_Cashly extends Pay_Payment_Model_Paymentmethod {
    const OPTION_ID = 1981;
    protected $_paymentOptionId = 1981;
    protected $_code = 'pay_payment_cashly';
    protected $_formBlockType = 'pay_payment/form_cashly';

    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if(!$this->addressEqual($quote)){
            return false;
        }

        return parent::isApplicableToQuote($quote, $checksBitMask);
    }
}
    