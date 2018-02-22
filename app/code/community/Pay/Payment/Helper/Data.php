<?php


class Pay_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
    public static function getLanguage($store = null)
    {
        if (is_null($store)) {
            $store = Mage::app()->getStore();
        }

        $language = $store->getConfig('pay_payment/general/user_language');

        if ($language == 'browser' || empty($language)) {
            return self::getDefaultLanguage();
        } else {
            return $language;
        }
    }

    public static function splitAddress($strAddress)
    {
        return \Paynl\Helper::splitAddress(trim($strAddress));
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

    /**
     * @param $amountInclTax
     * @param $taxAmount
     *
     * @return mixed
     * @deprecated
     */
    public static function calculateTaxClass($amountInclTax, $taxAmount)
    {
        if ( ! $amountInclTax || ! $taxAmount) {
            return self::getTaxCodeFromRate(0);
        }
        $amountExclTax = $amountInclTax - $taxAmount;
        if ($amountExclTax == 0) { // prevent division by zero
            $taxRate = 0;
        } else {
            $taxRate = ($taxAmount / $amountExclTax) * 100;
        }

        return self::getTaxCodeFromRate($taxRate);
    }

    /**
     * @param $taxRate
     *
     * @return mixed
     * @deprecated
     */
    public static function getTaxCodeFromRate($taxRate)
    {
        $taxClasses     = array(
            0  => 'N',
            6  => 'L',
            21 => 'H'
        );
        $nearestTaxRate = self::nearest($taxRate, array_keys($taxClasses));

        return ($taxClasses[$nearestTaxRate]);
    }

    /**
     * @param $number
     * @param $numbers
     *
     * @return bool
     * @deprecated
     */
    public static function nearest($number, $numbers)
    {
        $output = false;
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

    protected static function getDefaultLanguage()
    {
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
            return self::parseDefaultLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
        } else {
            return self::parseDefaultLanguage(null);
        }
    }

    private static function parseDefaultLanguage($http_accept, $deflang = "en")
    {
        if (isset($http_accept) && strlen($http_accept) > 1) {
            $lang = array();
            # Split possible languages into array
            $x = explode(",", $http_accept);
            foreach ($x as $val) {
                #check for q-value and create associative array. No q-value means 1 by rule
                if (preg_match("/(.*);q=([0-1]{0,1}.[0-9]{0,4})/i", $val,
                    $matches)) {
                    $lang[$matches[1]] = (float)$matches[2] . '';
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
                        $qval    = (float)$value;
                        $deflang = $key;
                    }
                }
            }
        }

        return strtolower(substr($deflang, 0, 2));
    }

    public function getVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->Pay_Payment->version;
    }

    public function loginSDK($store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }

        $serviceId = $store->getConfig('pay_payment/general/serviceid');
        $apiToken  = $store->getConfig('pay_payment/general/apitoken');
        $tokenCode = $store->getConfig('pay_payment/general/tokencode');

        \Paynl\Config::setApiToken($apiToken);
        \Paynl\Config::setServiceId($serviceId);
        if ( ! empty($tokenCode)) {
            \Paynl\Config::setTokenCode($tokenCode);
        }

        $useBackupApi = Mage::getStoreConfig('pay_payment/general/use_backup_api', $store);
        $backupApiUrl = Mage::getStoreConfig('pay_payment/general/backup_api_url', $store);
        if ($useBackupApi == 1) {
            \Paynl\Config::setApiBase($backupApiUrl);
        }
    }

    /**
     * @param $ipAddress
     *
     * @return array
     * @deprecated
     */
    public function isPayIp($ipAddress)
    {
        return \Paynl\Validate::isPayServerIp($ipAddress);
    }

    public function isOptionAvailable($option_id, $store = null)
    {
        $option = $this->getOption($option_id, $store);

        return ! is_null($option->getInternalId());
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

    public function lockTransaction($transactionId)
    {
        $transaction  = $this->getTransaction($transactionId);
        $max_lock     = strtotime('+5 min');
        $current_lock = $transaction->getLockDate();
        if ($current_lock !== null) {
            $current_lock = strtotime($current_lock);
        }
        if ($current_lock !== null && $current_lock < $max_lock) {
            $obj_max_lock = new DateTime();
            $obj_max_lock->setTimestamp($max_lock);

            throw new Pay_Payment_Model_Transaction_LockException('Cannot lock transaction, transaction already locked until: ' . $obj_max_lock->format('H:i:s d-m-Y'));
        }
        $transaction->setLockDate(strtotime('now'));
        $transaction->save();

        return true;
    }

    /**
     * @param $transactionId
     *
     * @return Pay_Payment_Model_Mysql4_Transaction
     */
    public function getTransaction($transactionId)
    {
        $transaction = Mage::getModel('pay_payment/transaction')
                           ->getCollection()
                           ->addFieldToFilter('transaction_id', $transactionId)
                           ->getFirstItem();

        return $transaction;
    }

    public function removeLock($transactionId)
    {
        $transaction = $this->getTransaction($transactionId);
        $transaction->setLockDate(null);
        $transaction->save();
    }

    public function isOrderPaid($orderId)
    {
        /** @var Pay_Payment_Model_Mysql4_Transaction_Collection $transaction */
        $transaction = Mage::getModel('pay_payment/transaction')
                           ->getCollection()
                           ->addFieldToFilter('order_id', $orderId)
                           ->addFieldToFilter('status', Pay_Payment_Model_Transaction::STATE_SUCCESS);

        return $transaction->count() > 0;
    }

    public function getOptions($store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }
        $serviceId = $store->getConfig('pay_payment/general/serviceid');

        $options = Mage::getModel('pay_payment/option')->getCollection()
                       ->addFieldToFilter('service_id', $serviceId);

        return $options;
    }

    public function loadOptions($store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }

        $this->loginSDK($store);

        $paymentMethods = \Paynl\Paymentmethods::getList();

        $this->_saveOptions($paymentMethods, $store);
    }

    public function getPaymentChargeTaxClass($code)
    {
        $taxClass = Mage::getStoreConfig('payment/' . strval($code) . '/charge_tax_class');

        return $taxClass;
    }

    /**
     * Get payment charge
     *
     * @param string $code
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return float
     */
    public function getPaymentCharge($code, $quote = null)
    {
        if (is_null($quote)) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
        }
        $amount  = 0;
        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();


        $chargeType  = Mage::getStoreConfig('payment/' . strval($code) . '/charge_type');
        $chargeValue = Mage::getStoreConfig('payment/' . strval($code) . '/charge_value');

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

        return $amount;
    }

    private function getTerminals($store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }

        $this->loginSDK($store);
        try {
            $terminals = \Paynl\Instore::getAllTerminals();

            return $terminals->getList();
        } catch (Exception $e) {
            return array();
        }


    }

    private function _saveOptions($paymentMethods, $store = null)
    {
        if ($store == null) {
            $store = Mage::app()->getStore();
        }
        $serviceId = $store->getConfig('pay_payment/general/serviceid');

        $arrUsedOptionIds = array();
        foreach ($paymentMethods as $paymentMethod) {
            $image = 'https://www.pay.nl/images/payment_profiles/20x20/' . $paymentMethod['id'] . '.png';

            /**
             * @var Pay_Payment_Model_Option $objOption
             */
            $objOption = Mage::getModel('pay_payment/option')->getCollection()
                             ->addFieldToFilter('service_id', $serviceId)
                             ->addFieldToFilter('option_id', $paymentMethod['id'])
                             ->getFirstItem();

            $optionData = array(
                'option_id'   => (string)$paymentMethod['id'],
                'service_id'  => $serviceId,
                'name'        => (string)$paymentMethod['visibleName'],
                'image'       => $image,
                'update_date' => time(),
            );

            $objOption->addData($optionData);
            $objOption->save();

            $arrUsedOptionIds[] = $objOption->getInternalId();

            $arrUsedOptionSubIds = array();

            if ($paymentMethod['id'] == Pay_Payment_Model_Paymentmethod_Instore::OPTION_ID) {
                $paymentMethod['banks'] = array();

                $arrTerminals = $this->getTerminals($store);
                foreach ($arrTerminals as $terminal) {
                    $paymentMethod['banks'][] = array(
                        'id'          => $terminal['id'],
                        'name'        => $terminal['name'],
                        'visibleName' => $terminal['name']
                    );
                }
            }
            if ( ! empty($paymentMethod['banks']) &&
                 (
                     $paymentMethod['id'] == Pay_Payment_Model_Paymentmethod_Ideal::OPTION_ID ||
                     $paymentMethod['id'] == Pay_Payment_Model_Paymentmethod_Instore::OPTION_ID
                 )) {
                foreach ($paymentMethod['banks'] as $optionSub) {
                    $image = '';
                    if (isset($optionSub['image'])) {
                        $image = $optionSub['image'];
                    }
                    $optionSubData = array(
                        'option_sub_id'      => $optionSub['id'],
                        'option_internal_id' => $objOption->getInternalId(),
                        'name'               => $optionSub['visibleName'],
                        'image'              => $image,
                        'active'             => 1
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
            /**
             * @var Pay_Payment_Model_Optionsub[] $arrSubsToDelete
             */
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
}