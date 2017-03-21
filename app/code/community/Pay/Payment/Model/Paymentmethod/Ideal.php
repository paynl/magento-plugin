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
}
