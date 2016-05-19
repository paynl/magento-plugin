<?php

class Pay_Payment_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract
{

    const OPTION_ID = 0;

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    protected $_paymentOptionId;
    protected $_paymentOptionName;

    public function getPaymentOptionId()
    {
        return $this->_paymentOptionId;
    }

    public function isApplicableToQuote($quote, $checksBitMask)
    {
        $store = $quote->getStore();
        $limit_shipping = Mage::getStoreConfig('payment/' . $this->_code . '/limit_shipping', $store);
        if ($limit_shipping) {
            $disabled_shipping = Mage::getStoreConfig('payment/' . $this->_code . '/disabled_shippingmethods', $store);
            $disabled_shipping = explode(',', $disabled_shipping);

            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
            if (in_array($shippingMethod, $disabled_shipping)) return false;
        }

        return parent::isApplicableToQuote($quote, $checksBitMask);
    }

    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund()
    {
        $enable_refund = Mage::getStoreConfig('pay_payment/general/enable_refund');
        if (!$enable_refund) return false;
        else return parent::canRefund();
    }

    /**
     * Check partial refund availability for invoice
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        $enable_refund = Mage::getStoreConfig('pay_payment/general/enable_refund');
        if (!$enable_refund) return false;
        else return parent::canRefundPartialPerInvoice();
    }

    public function refund(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $store = $order->getStore();

        $serviceId = Mage::getStoreConfig('pay_payment/general/serviceid', $store);
        $apiToken = Mage::getStoreConfig('pay_payment/general/apitoken', $store);

        $useBackupApi = Mage::getStoreConfig('pay_payment/general/use_backup_api', $store);
        $backupApiUrl = Mage::getStoreConfig('pay_payment/general/backup_api_url', $store);
        if ($useBackupApi == 1) {
            Pay_Payment_Helper_Api::_setBackupApiUrl($backupApiUrl);
        }

        $parentTransactionId = $payment->getParentTransactionId();

        $apiRefund = Mage::helper('pay_payment/api_refund');
        $apiRefund instanceof Pay_Payment_Helper_Api_Refund;
        $apiRefund->setApiToken($apiToken);
        $apiRefund->setServiceId($serviceId);

        $apiRefund->setTransactionId($parentTransactionId);
        $amount = (int)round($amount * 100);
        $apiRefund->setAmount($amount);

        $apiRefund->doRequest();

        return $this;
    }


    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('pay_payment/checkout/redirect');
    }

//    /**
//     * Instantiate state and set it to state object
//     * @param string $paymentAction
//     * @param Varien_Object
//     */
    public function initialize($paymentAction, $stateObject)
    {
        $session = Mage::getSingleton('checkout/session');
        /* @var $session Mage_Checkout_Model_Session */

        $session->setOptionId($this->getPaymentOptionId());
        return true;
    }
}