<?php

class Pay_Payment_Model_Paymentmethod_Ideal extends Pay_Payment_Model_Paymentmethod {

    const OPTION_ID = 10;

    protected $_paymentOptionId = 10;
    protected $_code = 'pay_payment_ideal';
    protected $_formBlockType = 'pay_payment/form_ideal';

    public function __construct() {
        $store = Mage::app()->getStore();
        $bankSelectType = $store->getConfig('payment/pay_payment_ideal/bank_select_type');

        parent::__construct();
    }

    
//    public function assignData($data) {
//        $store = Mage::app()->getStore();
//        $bankSelectType = $store->getConfig('payment/pay_payment_ideal/bank_select_type');
////        $bankSelectType = Mage::getStoreConfig('payment/pay_payment_ideal/bank_select_type', Mage::app()->getStore());
//        if($bankSelectType == 'none'){
//            return $this;
//        }
//
//        if (!($data instanceof Varien_Object)) {
//            $data = new Varien_Object($data);
//        }
//        $session = Mage::getSingleton('checkout/session');
//        /* @var $session Mage_Checkout_Model_Session */
//
//        $session->setOptionSubId();
//
//        if ($data->getOptionSub()) {
//            $optionSub = $data->getOptionSub();
//            $session->setOptionSubId($optionSub);
//        }
//
//        return parent::assignData($data);
//    }

}
