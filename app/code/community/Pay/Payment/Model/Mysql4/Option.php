<?php

class Pay_Payment_Model_Mysql4_Option extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('pay_payment/option', 'internal_id');
    }

}
