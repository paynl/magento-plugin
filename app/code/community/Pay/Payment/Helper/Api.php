<?php

class Pay_Payment_Helper_Api extends Mage_Core_Helper_Abstract {

    const REQUEST_TYPE_POST = 1;
    const REQUEST_TYPE_GET = 0;

    protected $_apiUrl = 'https://rest-api.pay.nl';
    protected $_version = 'v3';
    protected $_controller = '';
    protected $_action = '';
    protected $_serviceId = '';
    protected $_apiToken = '';
    protected $_requestType = self::REQUEST_TYPE_POST;
    protected $_postData = array();

    protected static $_backupApiUrl;

    public static function _setBackupApiUrl($url){
        self::$_backupApiUrl = $url;
    }

    public function setServiceId($serviceId) {
        $this->_serviceId = $serviceId;
    }

    public function setApiToken($apiToken) {
        $this->_apiToken = $apiToken;
    }

    protected function _getPostData() {

        return $this->_postData;
    }

    protected function _processResult($data) {
        return $data;
    }

    private function _getApiUrl() {
        if ($this->_version == '') {
            throw Mage::exception('Pay_Payment_Helper_Api', 'version not set', 1);
        }
        if ($this->_controller == '') {
            throw Mage::exception('Pay_Payment_Helper_Api', 'controller not set', 1);
        }
        if ($this->_action == '') {
            throw Mage::exception('Pay_Payment_Helper_Api', 'action not set', 1);
        }

        $apiUrl = $this->_apiUrl;
        if(!empty(self::$_backupApiUrl)){
            $apiUrl = self::$_backupApiUrl;
        }

        return $apiUrl . '/' . $this->_version . '/' . $this->_controller . '/' . $this->_action . '/json/';
    }

    public function doRequest() {
        if ($this->_getPostData()) {
            
            $url = $this->_getApiUrl();

            $data = $this->_getPostData();            
            
            $strData = http_build_query($data);

            $apiUrl = $url;

            $ch = curl_init();
            if ($this->_requestType == self::REQUEST_TYPE_GET) {
                $apiUrl .= '?' . $strData;          
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $strData);
            }
            
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $result = curl_exec($ch);

            if ($result == false) {
                $error = curl_error($ch);
                throw Mage::exception('Pay_Payment_Helper_Api', 'Curl Exception: '.$error, 1);
            }
            
            curl_close($ch);

            $arrResult = json_decode($result, true);

            if ($this->validateResult($arrResult)) {
                return $this->_processResult($arrResult);
            }
        }
    }

    protected function validateResult($arrResult) {
        if ($arrResult['request']['result'] == 1) {
            return true;
        } else {
            if(isset($arrResult['request']['errorId']) && isset($arrResult['request']['errorMessage']) ){
                throw Mage::exception('Pay_Payment_Helper_Api', $arrResult['request']['errorId'] . ' - ' . $arrResult['request']['errorMessage']);
            } else {
                if($arrResult['status'] == 'FALSE' && isset($arrResult['error'])){
                    throw Mage::exception('Pay_Payment_Helper_Api', 'API error: '.$arrResult['error']);
                } else {
                    throw Mage::exception('Pay_Payment_Helper_Api', 'Unexpected api result: '.  print_r($arrResult, true));
                }
            }
        }
    }
    
}
