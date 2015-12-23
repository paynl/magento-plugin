<?php

class Pay_Payment_Helper_Api_Refund extends Pay_Payment_Helper_Api {

    protected $_version = 'v5';
    protected $_controller = 'transaction';
    protected $_action = 'refund';
  
    
    protected $_amount = '';
    protected $_description = '';
    
    public function setTransactionId($transactionId){      
        $this->_postData['transactionId'] = $transactionId;
    }
    
    public function setAmount($amount){      
        $this->_amount = $amount;
    }
    public function setDescription($description){      
        $this->_description = $description;
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
        if($this->_amount != ''){
            $data['amount'] = $this->_amount;
        }
        if($this->_description != ''){
            $data['description'] = $this->_description;
        }
        return $data;
    }
}
