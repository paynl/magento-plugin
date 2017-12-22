<?php

class Pay_Payment_Helper_Voucher extends Mage_Core_Helper_Abstract {
    /**
     * @var Mage_Core_Model_Store
     */
    private $store;

    /**
     * @var Pay_Payment_Helper_Data
     */
    private $pay_helper;

    public function __construct()
    {
        $this->pay_helper =  Mage::helper('pay_payment');
    }

    /**
     * @param Mage_Core_Model_Store $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * @return Mage_Core_Model_Store
     */
    private function getStore(){
        if($this->store) return $this->store;

        return Mage::app()->getStore();
    }

    public function getBalance($cardNumber){
        $this->pay_helper->loginSDK($this->getStore());
        $voucher = \Paynl\Voucher::get($cardNumber);
        return $voucher->getBalance();
    }

    public function capture($cardNumber, $amount, $pincode = null){
        $this->pay_helper->loginSDK($this->getStore());
        $options = array();
        $options['cardNumber'] = $cardNumber;
        $options['amount'] = $amount;

        if($pincode) $options['pincode'] = $pincode;
        return \Paynl\Voucher::charge($options);
    }
}
