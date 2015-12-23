<?php
class Pay_Payment_Helper_Api_Ispayserverip extends Pay_Payment_Helper_Api {

    protected $_version = 'v1';
    protected $_controller = 'Validate';
    protected $_action = 'isPayServerIp';
  
    public function setIpaddress($ipAddress){      
        $this->_postData['ipAddress'] = $ipAddress;
    }
    
    protected function validateResult($result) {
        return true;
    }
    
}
