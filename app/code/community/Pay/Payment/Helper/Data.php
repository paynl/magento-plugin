<?php

class Pay_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function isPayIp($ipAddress)
    {
        $helperApi = Mage::helper('pay_payment/api_ispayserverip');
        $helperApi instanceof Pay_Payment_Helper_Api_Ispayserverip;
        $helperApi->setIpaddress($ipAddress);
        $result    = $helperApi->doRequest();
        return $result['result'] == 1;
    }

    public function isOptionAvailable($option_id, $store = null)
    {
        $option = $this->getOption($option_id, $store);
        return !is_null($option->getInternalId());
    }

    public function getTransaction($transactionId)
    {
        $transaction = Mage::getModel('pay_payment/transaction')
            ->getCollection()
            ->addFieldToFilter('transaction_id', $transactionId)
            ->getFirstItem();
        return $transaction;
    }
    public function isOrderPaid($orderId){
        $transactions = Mage::getModel('pay_payment/transaction')
            ->getCollection()
            ->addFieldToFilter('order_id', $orderId);

        
    }

    public function getOptions($store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }
        $serviceId = $store->getConfig('pay_payment/general/serviceid');
        //$serviceId = Mage::getStoreConfig('pay_payment/general/serviceid', $store);

        $options = Mage::getModel('pay_payment/option')->getCollection()
            ->addFieldToFilter('service_id', $serviceId);
        return $options;
    }

    public function getOption($option_id, $store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }
        $serviceId = $store->getConfig('pay_payment/general/serviceid');
        //$serviceId = Mage::getStoreConfig('pay_payment/general/serviceid', $store);

        $option = Mage::getModel('pay_payment/option')->getCollection()
            ->addFieldToFilter('service_id', $serviceId)
            ->addFieldToFilter('option_id', $option_id)
            ->getFirstItem();
        return $option;
    }

    private function _saveOptions($data, $store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }
        $serviceId = $store->getConfig('pay_payment/general/serviceid');
        //$serviceId = Mage::getStoreConfig('pay_payment/general/serviceid', $store);

        $service        = $data['service'];
        $paymentOptions = $data['paymentOptions'];

        $imageBasePath = (string) $service['basePath'];

        $arrUsedOptionIds = array();

        foreach ($paymentOptions as $paymentOption) {
            $image = $imageBasePath.$paymentOption['path'].$paymentOption['img'];

            //Laden
            $objOption = Mage::getModel('pay_payment/option')->getCollection()
                ->addFieldToFilter('service_id', $serviceId)
                ->addFieldToFilter('option_id', $paymentOption['id'])
                ->getFirstItem();

            $optionData = array(
                'option_id' => (string) $paymentOption['id'],
                'service_id' => $serviceId,
                'name' => (string) $paymentOption['visibleName'],
                'image' => $image,
                'update_date' => time(),
            );

            $objOption->addData($optionData);
            $objOption->save();

            $arrUsedOptionIds[] = $objOption->getInternalId();

            $arrUsedOptionSubIds = array();

            if (!empty($paymentOption['paymentOptionSubList']) && $paymentOption['id']
                == 10) {
                foreach ($paymentOption['paymentOptionSubList'] as $optionSub) {
                    $optionSubData = array(
                        'option_sub_id' => $optionSub['id'],
                        'option_internal_id' => $objOption->getInternalId(),
                        'name' => $optionSub['name'],
                        'image' => $imageBasePath.$optionSub['path'].$optionSub['img'],
                        'active' => $optionSub['state']
                    );

                    $objOptionSub = Mage::getModel('pay_payment/optionsub')->getCollection()
                        ->addFieldToFilter('option_sub_id', $optionSub['id'])
                        ->addFieldToFilter('option_internal_id',
                            $objOption->getInternalId())
                        ->getFirstItem();

                    /* @var $objOptionSub Pay_Payment_Model_Optionsub */
                    $objOptionSub->addData($optionSubData);
                    $objOptionSub->save();

                    $arrUsedOptionSubIds[] = $objOptionSub->getInternalId();
                }
            }
            //Alle subs die niet zijn opnieuw zijn binnengekomen verwijderen
            $arrSubsToDelete = Mage::getModel('pay_payment/optionsub')
                ->getCollection()
                ->addFieldToFilter('option_internal_id',
                    $objOption->getInternalId())
                ->addFieldToFilter('internal_id',
                array('nin' => $arrUsedOptionSubIds));

            foreach ($arrSubsToDelete as $subToDelete) {
                $subToDelete->delete();
            }
        }
        $arrOptionsToDelete = Mage::getModel('pay_payment/option')
            ->getCollection()
            ->addFieldToFilter('service_id', $serviceId)
            ->addFieldToFilter('internal_id', array('nin' => $arrUsedOptionIds));
        foreach ($arrOptionsToDelete as $optionToDelete) {
            $optionToDelete->delete();
        }
    }

    public function loadOptions($store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }
        $serviceId = $store->getConfig('pay_payment/general/serviceid');
        //$serviceId = Mage::getStoreConfig('pay_payment/general/serviceid', $store);
        $apiToken  = $store->getConfig('pay_payment/general/apitoken');
        //$apiToken = Mage::getStoreConfig('pay_payment/general/apitoken', $store);

        $useBackupApi = Mage::getStoreConfig('pay_payment/general/use_backup_api', $store);
        $backupApiUrl = Mage::getStoreConfig('pay_payment/general/backup_api_url', $store);
        if($useBackupApi == 1){
            Pay_Payment_Helper_Api::_setBackupApiUrl($backupApiUrl);
        }

        $api = Mage::helper('pay_payment/api_getservice');

        /* @var $api Pay_Payment_Helper_Api_GetService */
        $api->setApiToken($apiToken);
        $api->setServiceId($serviceId);

        $data = $api->doRequest();

        $this->_saveOptions($data, $store);
    }

    public function getPaymentChargeTaxClass($code)
    {
        $taxClass = Mage::getStoreConfig('payment/'.strval($code).'/charge_tax_class');
        return $taxClass;
    }

    /**
     * Get payment charge
     * @param string $code
     * @param Mage_Sales_Model_Quote $quote
     * @return float
     */
    public function getPaymentCharge($code, $quote = null)
    {
        if (is_null($quote)) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
        }
        $amount  = 0;
        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();


        $chargeType  = Mage::getStoreConfig('payment/'.strval($code).'/charge_type');
        $chargeValue = Mage::getStoreConfig('payment/'.strval($code).'/charge_value');

        if ($chargeType == "percentage") {
            //totaal moet berekend worden
            $subTotal = $address->getSubtotalInclTax();
            $shipping = $address->getShippingInclTax();
            $discount = $address->getDiscountAmount();

            $grandTotal = $subTotal + $shipping + $discount;
            $amount     = $grandTotal * floatval($chargeValue) / 100;
        } else {
            $amount = floatval($chargeValue);
        }
        //  }	
        ////echo $amount;	
        //return Mage::helper('core')->formatPrice($amount);
        return $amount;
    }

    public static function getLanguage()
    {
        $store = Mage::app()->getStore();

        $language = $store->getConfig('pay_payment/general/user_language');
//        $language = Mage::getStoreConfig('pay_payment/general/user_language', $store);

        if ($language == 'browser' || empty($language)) {
            return self::getDefaultLanguage();
        } else {
            return $language;
        }
    }

    protected static function getDefaultLanguage()
    {

        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
                return self::parseDefaultLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
        else return self::parseDefaultLanguage(NULL);
    }

    private static function parseDefaultLanguage($http_accept, $deflang = "en")
    {
        if (isset($http_accept) && strlen($http_accept) > 1) {
            # Split possible languages into array
            $x = explode(",", $http_accept);
            foreach ($x as $val) {
                #check for q-value and create associative array. No q-value means 1 by rule
                if (preg_match("/(.*);q=([0-1]{0,1}.[0-9]{0,4})/i", $val,
                        $matches)) {
                    $lang[$matches[1]] = (float) $matches[2].'';
                } else {
                    $lang[$val] = 1.0;
                }
            }

            $languages = new Pay_Payment_Model_Source_Language();

            $arrLanguages          = $languages->toOptionArray();
            $arrAvailableLanguages = array();
            foreach ($arrLanguages as $language) {
                $arrAvailableLanguages[] = $language['value'];
            }

            //laatste er af halen
            array_pop($arrAvailableLanguages);

            #return default language (highest q-value)
            $qval = 0.0;
            foreach ($lang as $key => $value) {
                $languagecode = strtolower(substr($key, 0, 2));

                if (in_array($languagecode, $arrAvailableLanguages)) {
                    if ($value > $qval) {
                        $qval    = (float) $value;
                        $deflang = $key;
                    }
                }
            }
        }
        return strtolower(substr($deflang, 0, 2));
    }

    public static function splitAddress($strAddress)
    {
        $strAddress = trim($strAddress);

        $a               = preg_split('/([0-9]+)/', $strAddress, 2,
            PREG_SPLIT_DELIM_CAPTURE);
        $strStreetName   = trim(array_shift($a));
        $strStreetNumber = trim(implode('', $a));

        if (empty($strStreetName)) { // American address notation
            $a = preg_split('/([a-zA-Z]{2,})/', $strAddress, 2,
                PREG_SPLIT_DELIM_CAPTURE);

            $strStreetNumber = trim(array_shift($a));
            $strStreetName   = implode(' ', $a);
        }

        return array($strStreetName, $strStreetNumber);
    }

    public static function getIp()
    {

        //Just get the headers if we can or else use the SERVER global
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = $_SERVER;
        }
        //Get the forwarded IP if it exists
        if (array_key_exists('X-Forwarded-For', $headers)) {
            $the_ip = $headers['X-Forwarded-For'];
        } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers)) {
            $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
        } else {
            $the_ip = $_SERVER['REMOTE_ADDR'];
        }
        $arrIp  = explode(',', $the_ip);
        $the_ip = $arrIp[0];

        $the_ip = filter_var(trim($the_ip), FILTER_VALIDATE_IP);

        return $the_ip;
    }

    public static function nearest($number, $numbers)
    {
        $output = FALSE;
        $number = intval($number);
        if (is_array($numbers) && count($numbers) >= 1) {
            $NDat = array();
            foreach ($numbers as $n) {
                $NDat[abs($number - $n)] = $n;
            }
            ksort($NDat);
            $NDat   = array_values($NDat);
            $output = $NDat[0];
        }
        return $output;
    }

    public static function getTaxCodeFromRate($taxRate)
    {
        $taxClasses     = array(
            0 => 'N',
            6 => 'L',
            21 => 'H'
        );
        $nearestTaxRate = self::nearest($taxRate, array_keys($taxClasses));

        return($taxClasses[$nearestTaxRate]);
    }

    public static function calculateTaxClass($amountInclTax, $taxAmount)
    {
        $amountExclTax = $amountInclTax - $taxAmount;
        $taxRate       = ($taxAmount / $amountExclTax) * 100;

        return self::getTaxCodeFromRate($taxRate);
    }
}