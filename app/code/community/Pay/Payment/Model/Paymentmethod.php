<?php

class Pay_Payment_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract
{
    const OPTION_ID = 0;

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;

    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canVoid = true;

    protected $_canCapturePartial = false;

    protected $_paymentOptionName;

    protected $_additionalData = array();

    /**
     * @var Pay_Payment_Helper_Data
     */
    protected $helperData;
    /**
     * @var Pay_Payment_Helper_Order
     */
    protected $helperOrder;

    public function __construct()
    {
        $this->helperData = Mage::helper('pay_payment');
        $this->helperOrder = Mage::helper('pay_payment/order');
        $show_in_admin = Mage::getStoreConfig('pay_payment/general/show_in_admin');
        if ($show_in_admin) $this->_canUseInternal = true;

        parent::__construct();
    }

    public function isApplicableToQuote($quote, $checksBitMask)
    {
        $store = $quote->getStore();
        $limit_shipping = Mage::getStoreConfig('payment/' . $this->_code . '/limit_shipping', $store);
        if ($limit_shipping) {
            $disabled_shipping = Mage::getStoreConfig('payment/' . $this->_code . '/disabled_shippingmethods', $store);
            $disabled_shipping = explode(',', $disabled_shipping);

            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
            if (in_array($shippingMethod, $disabled_shipping)) return false;
        }

        $limit_currency = Mage::getStoreConfig('payment/' . $this->_code . '/limit_currency', $store);
        if ($limit_currency) {
            $allowed_currencies = Mage::getStoreConfig('payment/' . $this->_code . '/specificcurrency', $store);
            $allowed_currencies = explode(',', $allowed_currencies);

            if (!in_array($quote->getQuoteCurrencyCode(), $allowed_currencies)) return false;
        }

        $only_equal_addresses = Mage::getStoreConfig('payment/' . $this->_code . '/only_equal_addresses', $store);
        if ($only_equal_addresses && !$this->addressEqual($quote)) {
            return false;
        }

        return parent::isApplicableToQuote($quote, $checksBitMask);
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $transaction = $payment->getAuthorizationTransaction();

        if (!$transaction) {
            Mage::throwException('Cannot find authorize transaction');
        }
        $transactionId = $transaction->getTxnId();

        $order = $payment->getOrder();
        $store = $order->getStore();

        $payTransaction = $this->helperData->getTransaction($transactionId);

        $this->helperData->loginSDK($store, $payTransaction->getGatewayUrl());

        $this->helperData->lockTransaction($transactionId);

        $result = \Paynl\Transaction::capture($transactionId);

        $this->helperData->removeLock($transactionId);

        return $result;
    }


    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }

    public function void(Varien_Object $payment)
    {
        $transaction = $payment->getAuthorizationTransaction();

        if (!$transaction) {
            Mage::throwException('Cannot find authorize transaction');
        }
        $transactionId = $transaction->getTxnId();

        $order = $payment->getOrder();
        $store = $order->getStore();


        $this->helperData->lockTransaction($transactionId);
        $payTransaction = $this->helperData->getTransaction($transactionId);

        $this->helperData->loginSDK($store, $payTransaction->getGatewayUrl());
        $result = \Paynl\Transaction::void($transactionId);

        $this->helperData->removeLock($transactionId);

        return $result;
    }

    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund()
    {
        $enable_refund = Mage::getStoreConfig('pay_payment/general/enable_refund');
        if (!$enable_refund) return false;
        else return parent::canRefund();
    }

    /**
     * Check partial refund availability for invoice
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        $enable_refund = Mage::getStoreConfig('pay_payment/general/enable_refund');
        if (!$enable_refund) return false;
        else return parent::canRefundPartialPerInvoice();
    }

    public function refund(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $store = $order->getStore();


        $parentTransactionId = $payment->getParentTransactionId();

        if ($pos = strpos($parentTransactionId, '-')) {
            $parentTransactionId = substr($parentTransactionId, 0, $pos);
        }
        try {
            $payTransaction = $this->helperData->getTransaction($parentTransactionId);

            $this->helperData->loginSDK($store, $payTransaction->getGatewayUrl());

            \Paynl\Transaction::refund($parentTransactionId, $amount);
        } catch (Exception $e) {
            // exception needs to be thrown this way, otherwise we don't get the message in the admin
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    public static function startPayment(Mage_Sales_Model_Order $order, $transaction_amount = null, $subId = null)
    {
        $store = $order->getStore();
        /** @var Pay_Payment_Helper_Data $helperData */
        $helperData = Mage::helper('pay_payment');

        $extended_logging = Mage::getStoreConfig('pay_payment/general/extended_logging', $store);

        if ($extended_logging) $order->addStatusHistoryComment('PAY. starting payment for order: ' . $order->getIncrementId());

        $helperData->loginSDK($store);
        $usedGateway = $helperData->getGatewayUrl($store);

        Mage::log('Starting payment for order: ' . $order->getId(), null, 'paynl.log');

        $arrStartData = static::getTransactionStartData($order);
        if ($subId !== null) {
            $arrStartData['bank'] = $subId;
        }

        if ($transaction_amount == null) {
            $transaction_amount = $arrStartData['amount'];
        }

        $arrStartData['amount'] = $transaction_amount;

        try {
            if ($extended_logging) $order->addStatusHistoryComment('Calling Pay api to start transaction');
            Mage::log('Calling Pay api to start transaction', null, 'paynl.log');

            $time_before = microtime(true);
            $objStartResult = \Paynl\Transaction::start($arrStartData);
            $time_after = microtime(true);

            $duration = $time_after - $time_before;
            $duration = number_format($duration, 4);
            if ($extended_logging) $order->addStatusHistoryComment('Transaction started in ' . $duration . ' seconds');

        } catch (Exception $e) {
            $order->addStatusHistoryComment("Creating transaction failed, Exception: " . $e->getMessage());
            $order->save();
            Mage::log("Creating transaction failed, Exception: " . $e->getMessage(), null, 'paynl.log');
            // Reset previous errors
            Mage::getSingleton('checkout/session')->getMessages(true);

            // cart restoren
            $restoreCart = Mage::getStoreConfig('pay_payment/general/restore_cart', Mage::app()->getStore());
            if ($restoreCart) {
                $quoteModel = Mage::getModel('sales/quote');
                $quoteId = $order->getQuoteId();

                /**
                 * @var $quote Mage_Sales_Model_Quote
                 */
                $quote = $quoteModel->load($quoteId);

                $quote->setIsActive(true)->save();
            }

            // Add error to cart
            Mage::getSingleton('checkout/session')->addError(Mage::helper('pay_payment')->__('Er is een storing bij de door u gekozen betaalmethode of bank. Kiest u alstublieft een andere betaalmethode of probeer het later nogmaals'));
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            // Redirect via header

            return array('url' => Mage::getUrl('checkout/cart'), 'exception' => $e);
        }

        $transaction = Mage::getModel('pay_payment/transaction');

        $transactionId = $objStartResult->getTransactionId();

        Mage::log('Transaction started, transactionId: ' . $transactionId, null, 'paynl.log');


        $transaction->setData(
            array(
                'transaction_id' => $transactionId,
                'service_id' => \Paynl\Config::getServiceId(),
                'option_id' => static::OPTION_ID,
                'option_sub_id' => null,
                'amount' => round($transaction_amount * 100),
                'order_id' => $order->getId(),
                'status' => Pay_Payment_Model_Transaction::STATE_PENDING,
                'created' => time(),
                'last_update' => time(),
                'gateway_url' => $usedGateway
            ));

        $transaction->save();

        //redirecten
        $url = $objStartResult->getRedirectUrl();

        $payment = $order->getPayment();

        $order->addStatusHistoryComment('Transactie gestart, transactieId: ' . $transactionId);
        $order->addStatusHistoryComment($url);

        $sendMail = Mage::getStoreConfig('payment/' . $payment->getMethod() . '/send_mail', $order->getStore());

        if ($sendMail == 'start') {
            $order->sendNewOrderEmail();
        }
        if (isset($objStartResult->getData()['terminal']['hash'])) {
            $hash = $objStartResult->getData()['terminal']['hash'];
            $payment->setAdditionalInformation('paynl_instore_hash', $hash);
        }
        $payment->setAdditionalInformation('paynl_url', $url);
        $payment->setAdditionalInformation('paynl_order_id', $transactionId);
        $payment->setAdditionalInformation('paynl_accept_code', $objStartResult->getPaymentReference());

        $payment->save();
        $order->save();
        $result = array(
            'url' => $url,
            'transactionId' => $transactionId
        );


        return $result;
    }

    protected static function getTransactionStartData(Mage_Sales_Model_Order $order)
    {
        $store = $order->getStore();
        $helperData = Mage::helper('pay_payment');

        $session = Mage::getSingleton('checkout/session');

        $sendOrderData = $store->getConfig('pay_payment/general/send_order_data');
        $onlyBaseCurrency = $store->getConfig('pay_payment/general/only_base_currency') == 1;
        $testMode = $store->getConfig('pay_payment/general/testmode');

        $additionalData = $session->getPaynlPaymentData();

        $optionId = static::OPTION_ID;
        $optionSubId = isset($additionalData['option_sub']) ? $additionalData['option_sub'] : null;

        $ipAddress = $order->getRemoteIp();
        if (empty($ipAddress)) $ipAddress = \Paynl\Helper::getIp();
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = substr($ipAddress, 0, strpos($ipAddress, ','));
        }

        $amount = $order->getGrandTotal();
        $currency = $order->getOrderCurrencyCode();
        if ($onlyBaseCurrency) {
            $amount = $order->getBaseGrandTotal();
            $currency = $order->getBaseCurrencyCode();
        }

        $arrStartData = array(
            'amount' => $amount,
            'testmode' => $testMode,
            'returnUrl' => Mage::getUrl('pay_payment/order/return', array('_store' => $store->getCode())),
            'exchangeUrl' => Mage::getUrl('pay_payment/order/exchange', array('_store' => $store->getCode())),
            'paymentMethod' => $optionId,
            'orderNumber' => $order->getIncrementId(),
            'description' => $order->getIncrementId(),
            'currency' => $currency,
            'extra1' => $order->getIncrementId(),
            'extra2' => $order->getCustomerEmail(),
            'ipAddress' => $ipAddress,
            'language' => $helperData->getLanguage($order->getStore())
        );
        $arrCompany = array();
        if ($order->getShippingAddress() && $order->getShippingAddress()->getCompany()) {
            $arrCompany['name'] = $order->getShippingAddress()->getCompany();
        } elseif ($order->getBillingAddress() && $order->getBillingAddress()->getCompany()) {
            $arrCompany['name'] = $order->getBillingAddress()->getCompany();
        }
        if ($order->getCustomerTaxvat()) {
            $arrCompany['vatNumber'] = substr($order->getCustomerTaxvat(), 0, 32);
        }

        $countryId = null;
        if ($order->getShippingAddress() && $order->getShippingAddress()->getCountryId()) {
            $countryId = $order->getShippingAddress()->getCountryId();
        } elseif ($order->getBillingAddress() && $order->getBillingAddress()->getCountryId()) {
            $countryId = $order->getBillingAddress()->getCountryId();
        }
        if (!is_null($countryId)) {
            $countryCode = Mage::getModel('directory/country')->load($countryId)->getIso2Code();
            $arrCompany['countryCode'] = $countryCode;
        }

        if (isset($additionalData['kvknummer']) && !empty($additionalData['kvknummer'])) {
            $arrCompany['cocNumber'] = $additionalData['kvknummer'];
        }

        if (!empty($arrCompany)) {
            $arrStartData['company'] = $arrCompany;
        }
        if (!is_null($optionSubId)) {
            $arrStartData['bank'] = $optionSubId;
        }
        if (isset($additionalData['valid_days'])) {
            $arrStartData['expireDate'] = date('d-m-Y H:i:s', strtotime('+' . $additionalData['valid_days'] . ' days'));
        }

        if ($sendOrderData) {
            $arrStartData['enduser'] = static::getEnduserData($order);
            $arrStartData['address'] = static::getShippingAddress($order);
            $arrStartData['invoiceAddress'] = static::getBillingAddress($order);
            $arrStartData['products'] = static::getProducts($order);
        }

        return $arrStartData;
    }

    private static function getEnduserData(Mage_Sales_Model_Order $order)
    {
        $session = Mage::getSingleton('checkout/session');
        $additionalData = $session->getPaynlPaymentData();
        if (
            isset($additionalData['birthday_day']) &&
            isset($additionalData['birthday_month']) &&
            isset($additionalData['birthday_year'])
        ) {
            $birthDate = $additionalData['birthday_year'] . '-' . $additionalData['birthday_month'] . '-' . $additionalData['birthday_day'];

            $order->setCustomerDob($birthDate);
            $order->save();
        }

        list($birthDate) = explode(' ', $order->getCustomerDob());
        if (!empty($birthDate)) {
            list($dobYear, $dobMonth, $dobDay) = explode('-', $birthDate);

            $birthDate = null;
            if ($dobDay && $dobMonth && $dobYear) {
                $birthDate = $dobDay . '-' . $dobMonth . '-' . $dobYear;
            }
        } else {
            $birthDate = null;
        }
        $iban = isset($additionalData['iban']) ? $additionalData['iban'] : null;

        $enduser = array(
            'birthDate' => $birthDate,
            'iban' => $iban
        );

        if ($order->getShippingAddress()) {
            $enduserAddress = $order->getShippingAddress();
        } else {
            $enduserAddress = $order->getBillingAddress();
        }
        if ($enduserAddress) {
            $enduser = array_merge($enduser, array(
                'initials' => static::getFirstname($enduserAddress),
                'lastName' => substr($enduserAddress->getLastname(), 0, 32),
                'phoneNumber' => $enduserAddress->getTelephone(),
                'emailAddress' => $enduserAddress->getEmail()
            ));
        }

        return $enduser;
    }

    protected static function getFirstname($address)
    {
        return substr($address->getFirstname(), 0, 1);
    }

    private static function getShippingAddress(Mage_Sales_Model_Order $order)
    {
        $objShippingAddress = $order->getShippingAddress();

        if (!$objShippingAddress) return array();

        $arrAddressFull = array();
        $arrAddressFull[] = $objShippingAddress->getStreet1();
        $arrAddressFull[] = $objShippingAddress->getStreet2();
        $arrAddressFull[] = $objShippingAddress->getStreet3();
        $arrAddressFull[] = $objShippingAddress->getStreet4();
        $arrAddressFull = array_unique($arrAddressFull);
        $addressFull = implode(' ', $arrAddressFull);
        $addressFull = str_replace("\n", ' ', $addressFull);
        $addressFull = str_replace("\r", ' ', $addressFull);

        list($address, $housenumber) = \Paynl\Helper::splitAddress($addressFull);


        $arrShippingAddress = array(
            'streetName' => $address,
            'houseNumber' => $housenumber,
            'zipCode' => $objShippingAddress->getPostcode(),
            'city' => $objShippingAddress->getCity(),
            'country' => $objShippingAddress->getCountry()
        );

        return $arrShippingAddress;
    }

    private static function getBillingAddress(Mage_Sales_Model_Order $order)
    {
        $objBillingAddress = $order->getBillingAddress();
        if (!$objBillingAddress) return array();

        $arrAddressFull = array();
        $arrAddressFull[] = $objBillingAddress->getStreet1();
        $arrAddressFull[] = $objBillingAddress->getStreet2();
        $arrAddressFull[] = $objBillingAddress->getStreet3();
        $arrAddressFull[] = $objBillingAddress->getStreet4();
        $arrAddressFull = array_unique($arrAddressFull);
        $addressFull = implode(' ', $arrAddressFull);
        $addressFull = str_replace("\n", ' ', $addressFull);
        $addressFull = str_replace("\r", ' ', $addressFull);

        list($address, $housenumber) = \Paynl\Helper::splitAddress($addressFull);


        $arrBillingAddress = array(
            'initials' => static::getFirstname($objBillingAddress),
            'lastName' => substr($objBillingAddress->getLastname(), 0, 32),
            'streetName' => $address,
            'houseNumber' => $housenumber,
            'zipCode' => $objBillingAddress->getPostcode(),
            'city' => $objBillingAddress->getCity(),
            'country' => $objBillingAddress->getCountry()
        );

        return $arrBillingAddress;
    }

    private static function getProducts(Mage_Sales_Model_Order $order)
    {
        $store = $order->getStore();
        $onlyBaseCurrency = $store->getConfig('pay_payment/general/only_base_currency') == 1;

        $helperData = Mage::helper('pay_payment');
        $arrProducts = array();

        $items = $order->getItemsCollection(array(), true);
        foreach ($items as $item) {
            /* @var $item Mage_Sales_Model_Order_Item */

            $price = $item->getPriceInclTax();
            if ($price == 0) {
                continue;
            }
            $price = $item->getPriceInclTax();
            if ($onlyBaseCurrency) {
                $price = $item->getBasePriceInclTax();
            }

            $product = array(
                'id' => $item->getId(),
                'name' => $item->getName(),
                'price' => $price,
                'vatPercentage' => $item->getTaxPercent(),
                'qty' => $item->getQtyOrdered(),
                'type' => \Paynl\Transaction::PRODUCT_TYPE_ARTICLE
            );
            $arrProducts[] = $product;
        }

        $discountAmount = $order->getDiscountAmount();
        if ($onlyBaseCurrency) {
            $discountAmount = $order->getBaseDiscountAmount();
        }
        if ($discountAmount < 0) {
            $discount = array(
                'id' => 'discount',
                'name' => 'Korting (' . $order->getDiscountDescription() . ')',
                'price' => $discountAmount,
                'vatPercentage' => 0,
                'qty' => 1,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_DISCOUNT
            );

            $arrProducts[] = $discount;
        }

        $shipping = $order->getShippingInclTax();
        $shipping_tax = $order->getShippingTaxAmount();
        if ($onlyBaseCurrency) {
            $shipping = $order->getBaseShippingInclTax();
            $shipping_tax = $order->getBaseShippingTaxAmount();
        }
        if ($shipping > 0) {
            $shipping = array(
                'id' => 'shipping',
                'name' => $order->getShippingDescription(),
                'price' => $shipping,
                'tax' => $shipping_tax,
                'qty' => 1,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_SHIPPING
            );

            $arrProducts[] = $shipping;
        }

        $extraFee = $order->getPaynlPaymentCharge();
        if ($onlyBaseCurrency) {
            $extraFee = $order->getPaynlBasePaymentCharge();
        }

        if ($extraFee != 0) {
            $payment = $order->getPayment();

            $code = $payment->getMethod();
            $taxClass = $helperData->getPaymentChargeTaxClass($code);

            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest($order->getShippingAddress(), $order->getBillingAddress());
            $request->setStore($order->getStore());
            $vatPercentage = $taxCalculationModel->getRate($request->setProductClassId($taxClass));

            $fee = array(
                'id' => 'paymentfee',
                'name' => Mage::getStoreConfig('pay_payment/general/text_payment_charge', $order->getStore()),
                'price' => $extraFee,
                'vatPercentage' => $vatPercentage,
                'qty' => 1,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_HANDLING
            );

            $arrProducts[] = $fee;
        }
        return $arrProducts;

    }

    public function getPaymentOptionId()
    {
        return $this->_paymentOptionId;
    }

    public function getOrderPlaceRedirectUrl()
    {

        return Mage::getUrl('pay_payment/checkout/redirect');
    }

    public function initialize($paymentAction, $stateObject)
    {
        $session = Mage::getSingleton('checkout/session');
        /* @var $session Mage_Checkout_Model_Session */

        $session->setOptionId($this->getPaymentOptionId());
        return true;
    }

    public function assignData($data)
    {
        $session = Mage::getSingleton('checkout/session');


        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $data = $data->getData();

        $this->_additionalData = $data;

        $session->setPaynlPaymentData($data);

        return parent::assignData($data);
    }


    protected static function addressEqual(Mage_Sales_Model_Quote $quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        if($shippingAddress->getSameAsBilling()) {
            return true;
        }

        if (strtolower($billingAddress->getStreet1()) !== strtolower($shippingAddress->getStreet1())) {
            return false;
        }
        if (strtolower($billingAddress->getStreet2()) !== strtolower($shippingAddress->getStreet2())) {
            return false;
        }
        if (strtolower($billingAddress->getStreet3()) !== strtolower($shippingAddress->getStreet3())) {
            return false;
        }
        if (strtolower($billingAddress->getStreet4()) !== strtolower($shippingAddress->getStreet4())) {
            return false;
        }
        if (strtolower($billingAddress->getPostcode()) !== strtolower($shippingAddress->getPostcode())) {
            return false;
        }
        if (strtolower($billingAddress->getRegion()) !== strtolower($shippingAddress->getRegion())) {
            return false;
        }
        if (strtolower($billingAddress->getCountryId()) !== strtolower($shippingAddress->getCountryId())) {
            return false;
        }

        return true;
    }
}
