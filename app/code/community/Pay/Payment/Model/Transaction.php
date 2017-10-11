<?php

class Pay_Payment_Model_Transaction extends Mage_Core_Model_Abstract{
      
    const STATE_PENDING = 'PENDING';
    const STATE_CANCELED = 'CANCELED';
    const STATE_SUCCESS = 'SUCCESS';
    const STATE_AUTHORIZED = 'AUTHORIZED';
    const STATE_VERIFY = 'VERIFY';

    public function __construct() {
        $this->_init('pay_payment/transaction');
        parent::__construct();
    }
}