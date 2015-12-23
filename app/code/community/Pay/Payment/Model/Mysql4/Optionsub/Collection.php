<?php

class Pay_Payment_Model_Mysql4_Optionsub_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    public function __construct() {
        $this->_init('pay_payment/optionsub');
        parent::__construct();
    }
}