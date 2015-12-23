<?php

class Pay_Payment_Model_Mysql4_Option_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    public function __construct() {
        $this->_init('pay_payment/option');
        parent::__construct();
    }
}