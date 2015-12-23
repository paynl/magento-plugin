<?php

class Pay_Payment_Helper_Api_Info extends Pay_Payment_Helper_Api {

    protected $_version = 'v3';
    protected $_controller = 'transaction';
    protected $_action = 'info';
  
    public function setTransactionId($transactionId){      
        $this->_postData['transactionId'] = $transactionId;
    }
    protected function _getPostData() {
        $data = parent::_getPostData();
        if ($this->_apiToken == '') {
            throw Mage::exception('Pay_Payment_Helper_Api', 'apiToken not set', 1);
        } else {
            $data['token'] = $this->_apiToken;
        }
        if(!isset($this->_postData['transactionId'])){
            throw Mage::exception('Pay_Payment_Helper_Api', 'transactionId is not set', 1);
        }
        return $data;
    }
}
