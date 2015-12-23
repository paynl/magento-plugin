<?php

class Pay_Payment_Helper_Api_Getservice extends Pay_Payment_Helper_Api {

    protected $_version = 'v3';
    protected $_controller = 'transaction';
    protected $_action = 'getService';

    protected function _getPostData() {
        $data = parent::_getPostData();

        // Checken of alle verplichte velden geset zijn 
        if ($this->_apiToken == '') {
            throw Mage::exception('Pay_Payment_Helper_Api', 'apiToken not set', 1);
        } else {
            $data['token'] = $this->_apiToken;
        }
        if (empty($this->_serviceId)) {
            throw Mage::exception('Pay_Payment_Helper_Api', 'apiToken not set', 1);
        } else {
            $data['serviceId'] = $this->_serviceId;
        }
        return $data;
    }
    protected function _processResult($arrReturn) {
        if (!$arrReturn['request']['result']) {
            return $arrReturn;
        }

        $arrReturn['paymentOptions'] = array();

        $countryOptionList = $arrReturn['countryOptionList'];
        unset($arrReturn['countryOptionList']);
        if (isset($countryOptionList) && is_array($countryOptionList)) {
            foreach ($countryOptionList AS $strCountrCode => $arrCountry) {
                foreach ($arrCountry['paymentOptionList'] AS $arrPaymentProfile) {

                    if (!isset($arrReturn['paymentOptions'][$arrPaymentProfile['id']])) {
                        $arrReturn['paymentOptions'][$arrPaymentProfile['id']] = array(
                            'id' => $arrPaymentProfile['id'],
                            'name' => $arrPaymentProfile['name'],
                            'visibleName' => $arrPaymentProfile['name'],
                            'img' => $arrPaymentProfile['img'],
                            'path' => $arrPaymentProfile['path'],
                            'paymentOptionSubList' => array(),
                            'countries' => array(),
                        );
                    }

                    if (!empty($arrPaymentProfile['paymentOptionSubList'])) {
                        $arrReturn['paymentOptions'][$arrPaymentProfile['id']]['paymentOptionSubList'] = $arrPaymentProfile['paymentOptionSubList'];
                    }


                    $arrReturn['paymentOptions'][$arrPaymentProfile['id']]['countries'][$strCountrCode] = array(
                        'id' => $strCountrCode,
                        'name' => $arrCountry['visibleName'],
                    );
                }
            }
        }
        return $arrReturn;
    }

}
