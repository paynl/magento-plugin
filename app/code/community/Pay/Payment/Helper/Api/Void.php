<?php

class Pay_Payment_Helper_Api_Void extends Pay_Payment_Helper_Api {

    protected $_version = 'v1';
    protected $_controller = 'Klarna';
    protected $_action = 'cancelAuthorization';

    
    public function setTransactionId($transactionId){      
        $this->_postData['orderId'] = $transactionId;
    }

    protected function _getPostData() {
        $data = parent::_getPostData();
        if ($this->_apiToken == '') {
            throw Mage::exception('Pay_Payment_Helper_Api', 'apiToken not set', 1);
        } else {
            $data['token'] = $this->_apiToken;
        }
        if(!isset($this->_postData['orderId'])){
            throw Mage::exception('Pay_Payment_Helper_Api', 'transactionId is not set', 1);
        }

        return $data;
    }
}
