<?php

class Pay_Payment_Model_Option extends Mage_Core_Model_Abstract{
    public function __construct() {
        $this->_init('pay_payment/option');
        parent::__construct();
    }
    public function getSubs(){
      
        $arrSubs = Mage::getModel('pay_payment/optionsub')->getCollection()
                ->addFieldToFilter('option_internal_id', $this->getInternalId());
        
        return $arrSubs;
    }
}