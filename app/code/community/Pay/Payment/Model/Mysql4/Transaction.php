<?php

class Pay_Payment_Model_Mysql4_Transaction extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('pay_payment/transaction', 'id');
    }

}
