<?php

class Pay_Payment_Model_Optionsub extends Mage_Core_Model_Abstract{
    public function __construct() {
        $this->_init('pay_payment/optionsub');
        parent::__construct();
    }
}