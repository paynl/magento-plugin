<?php
class Pay_Payment_Model_Paymentmethod_Wechatpay extends Pay_Payment_Model_Paymentmethod
{
    const OPTION_ID = 1978;
    protected $_paymentOptionId = 1978;
    protected $_code = 'pay_payment_wechatpay';
    protected $_formBlockType = 'pay_payment/form_wechatpay';

    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if ( ! $this->addressEqual($quote)) {
            return false;
        }

        return parent::isApplicableToQuote($quote, $checksBitMask);
    }
}