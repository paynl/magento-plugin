<?php

class Pay_Payment_Model_Mysql4_Optionsub extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('pay_payment/optionsub', 'internal_id');
    }

}
