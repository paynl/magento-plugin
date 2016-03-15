<?php

class Pay_Payment_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract {

    const OPTION_ID = 0;

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    
    protected $_paymentOptionId;
    protected $_paymentOptionName;

    public function getPaymentOptionId() {
        return $this->_paymentOptionId;
    }

    

    public function refund(Varien_Object $payment, $amount) {
        $payment instanceof Mage_Sales_Model_Order_Payment;
        $order = $payment->getOrder();
        $store = $order->getStore();
        
        $serviceId = Mage::getStoreConfig('pay_payment/general/serviceid', $store);
        $apiToken = Mage::getStoreConfig('pay_payment/general/apitoken', $store);

        $useBackupApi = Mage::getStoreConfig('pay_payment/general/use_backup_api', $store);
        $backupApiUrl = Mage::getStoreConfig('pay_payment/general/backup_api_url', $store);
        if($useBackupApi == 1){
            Pay_Payment_Helper_Api::_setBackupApiUrl($backupApiUrl);
        }

        //todo: Doe iets met de api
        $parentTransactionId = $payment->getParentTransactionId();
        
        $apiRefund = Mage::helper('pay_payment/api_refund');
        $apiRefund instanceof Pay_Payment_Helper_Api_Refund;
        $apiRefund->setApiToken($apiToken);
        $apiRefund->setServiceId($serviceId);
        
        $apiRefund->setTransactionId($parentTransactionId);        
        $amount = (int)round($amount*100);
        $apiRefund->setAmount($amount);
        
        $apiRefund->doRequest();
        
        // die($parentTransactionId);
        return $this;
    }

//    
//
//    public function processCreditmemo($creditmemo, $payment){//after refund
//        return $this;
//    } 

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('pay_payment/checkout/redirect');
    }

//    /**
//     * Instantiate state and set it to state object
//     * @param string $paymentAction
//     * @param Varien_Object
//     */
    public function initialize($paymentAction, $stateObject) {
        $session = Mage::getSingleton('checkout/session');
        /* @var $session Mage_Checkout_Model_Session */

        $session->setOptionId($this->getPaymentOptionId());
        return true;
    }
}