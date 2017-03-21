<?php

class Pay_Payment_Model_Paymentmethod_Klarna extends Pay_Payment_Model_Paymentmethod
{
    const OPTION_ID = 1717;
    protected $_paymentOptionId = 1717;
    protected $_code = 'pay_payment_klarna';
    protected $_formBlockType = 'pay_payment/form_klarna';

    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canVoid = true;

    protected $_canCapturePartial = false;

    /**
     * @return boolean
     */

    public function isApplicableToQuote($quote, $checksBitMask)
    {
        if (strtolower($quote->getShippingAddress()->getFirstname()) !== strtolower($quote->getBillingAddress()->getFirstname())) {
            return false;
        }
        if (strtolower($quote->getShippingAddress()->getLastname()) !== strtolower($quote->getBillingAddress()->getLastname())) {
            return false;
        }
        if ($quote->getShippingAddress()->getCountryId() != $quote->getBillingAddress()->getCountryId()) {
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

    public function capture(Varien_Object $payment, $amount)
    {
        $transaction = $payment->getAuthorizationTransaction();

        if (!$transaction) {
            Mage::throwException('Cannot find authorize transaction');
        }
        $transactionId = $transaction->getTxnId();

        $order = $payment->getOrder();
        $store = $order->getStore();

        $this->helperData->loginSDK($store);

        $this->helperData->lockTransaction($transactionId);

        $result = \Paynl\Transaction::capture($transactionId);

        $this->helperData->removeLock($transactionId);

        return $result;
    }

    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }

    public function void(Varien_Object $payment)
    {
        $transaction = $payment->getAuthorizationTransaction();

        if (!$transaction) {
            Mage::throwException('Cannot find authorize transaction');
        }
        $transactionId = $transaction->getTxnId();

        $order = $payment->getOrder();
        $store = $order->getStore();


        $this->helperData->lockTransaction($transactionId);

        $this->helperData->loginSDK($store);
        $result = \Paynl\Transaction::void($transactionId);

        $this->helperData->removeLock($transactionId);

        return $result;
    }
}
    