<?php

class Pay_Payment_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract
{

    const OPTION_ID = 0;

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

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

        return parent::isApplicableToQuote($quote, $checksBitMask);
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

        $this->helperData->loginSDK($store);

        $parentTransactionId = $payment->getParentTransactionId();

        if($pos = strpos($parentTransactionId, '-')){
            $parentTransactionId = substr($parentTransactionId, 0, $pos);
        }
        try{
            \Paynl\Transaction::refund($parentTransactionId, $amount);
        } catch (Exception $e){
            // exception needs to be thrown this way, otherwise we don't get the message in the admin
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    private function getProducts(Mage_Sales_Model_Order $order){
        $arrProducts = array();

        $items = $order->getItemsCollection(array(), true);
        foreach ($items as $item) {
            /* @var $item Mage_Sales_Model_Order_Item */

            $price = $item->getPriceInclTax();
            if($price == 0){
                continue;
            }
            $product = array(
                'id' => $item->getId(),
                'name' => $item->getName(),
                'price' => $item->getPriceInclTax(),
                'vatPercentage' => $item->getTaxPercent(),
                'qty' => $item->getQtyOrdered(),
                'type' => \Paynl\Transaction::PRODUCT_TYPE_ARTICLE
            );
            $arrProducts[] = $product;
        }

        $discountAmount = $order->getDiscountAmount();
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
        if ($shipping > 0) {
            $shipping = array(
                'id' => 'shipping',
                'name' => $order->getShippingDescription(),
                'price' => $order->getShippingInclTax(),
                'tax' => $order->getShippingTaxAmount(),
                'qty' => 1,
                'type' => \Paynl\Transaction::PRODUCT_TYPE_SHIPPING
            );

            $arrProducts[] = $shipping;
        }

        $extraFee = $order->getPaymentCharge();

        if ($extraFee != 0) {
            $payment = $order->getPayment();

            $code = $payment->getMethod();
            $taxClass = $this->helperData->getPaymentChargeTaxClass($code);

            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest($order->getShippingAddress(), $order->getBillingAddress());
            $request->setStore(Mage::app()->getStore());
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
    private function getBillingAddress(Mage_Sales_Model_Order $order){
        $objBillingAddress = $order->getBillingAddress();
        if(!$objBillingAddress) return array();

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
            'initials' => $objBillingAddress->getFirstname(),
            'lastName' => $objBillingAddress->getLastname(),
            'streetName' => $address,
            'houseNumber' => $housenumber,
            'zipCode' => $objBillingAddress->getPostcode(),
            'city' => $objBillingAddress->getCity(),
            'country' => $objBillingAddress->getCountry()
        );

        return $arrBillingAddress;
    }

    private function getShippingAddress(Mage_Sales_Model_Order $order){
        $objShippingAddress= $order->getShippingAddress();

        if(!$objShippingAddress) return array();

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

    private function getEnduserData(Mage_Sales_Model_Order $order){
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
        if(!empty(($birthDate))) {
            list($dobYear, $dobMonth, $dobDay) = explode('-', $birthDate);

            $birthDate = null;
            if ($dobDay && $dobMonth && $dobYear) {
                $birthDate = $dobDay . '-' . $dobMonth . '-' . $dobYear;
            }
        } else {
            $birthDate = null;
        }
        $iban = $additionalData['iban'] ? $additionalData['iban'] : null;

        $enduser = array(
            'birthDate' => $birthDate,
            'iban' => $iban
        );

        if($order->getShippingAddress()){
            $enduserAddress = $order->getShippingAddress();
        } else{
            $enduserAddress = $order->getBillingAddress();
        }
        if($enduserAddress){
            $enduser = array_merge($enduser, array(
                'initials' => $enduserAddress->getFirstname(),
                'lastName' => $enduserAddress->getLastname(),
                'phoneNumber' => $enduserAddress->getTelephone(),
                'emailAddress' => $enduserAddress->getEmail()
            ));
        }

        return $enduser;
    }

    private function getTransactionStartData(Mage_Sales_Model_Order $order){
        $store = $order->getStore();

        $session = Mage::getSingleton('checkout/session');

        $sendOrderData = $store->getConfig('pay_payment/general/send_order_data');
        $testMode = $store->getConfig('pay_payment/general/testmode');

        $additionalData = $session->getPaynlPaymentData();

        $optionId = $this->_paymentOptionId;
        $optionSubId = $additionalData['option_sub'] ? $additionalData['option_sub'] : null;

        $ipAddress= $order->getRemoteIp();
        if(empty($ipAddress)) $ipAddress = \Paynl\Helper::getIp();
        if(strpos($ipAddress, ',') !== false){
            $ipAddress = substr($ipAddress,0,strpos($ipAddress, ','));
        }

        $arrStartData = array(
            'amount' => $order->getGrandTotal(),
            'testmode' => $testMode,
            'returnUrl' => Mage::getUrl('pay_payment/order/return', array('_store' => $store->getCode())),
            'exchangeUrl' => Mage::getUrl('pay_payment/order/exchange', array('_store' => $store->getCode())),
            'paymentMethod' => $optionId,
            'description' => $order->getIncrementId(),
            'currency' => $order->getOrderCurrencyCode(),
            'extra1' => $order->getIncrementId(),
            'extra2' => $order->getCustomerEmail(),
            'ipAddress' => $ipAddress,
            'language' => $this->helperData->getLanguage($order->getStore())
        );
        $arrCompany = array();
        if($order->getShippingAddress()->getCompany()){
            $arrCompany['name'] = $order->getShippingAddress()->getCompany();
        } elseif($order->getBillingAddress()->getCompany()) {
            $arrCompany['name'] = $order->getBillingAddress()->getCompany();
        }
        if($order->getCustomerTaxvat()){
            $arrCompany['vatNumber'] = $order->getCustomerTaxvat();
        }

        $countryId = null;
        if($order->getShippingAddress()->getCountryId()){
            $countryId = $order->getShippingAddress()->getCountryId();
        }
        if($order->getBillingAddress()->getCountryId()){
            $countryId = $order->getBillingAddress()->getCountryId();
        }
        if(!is_null($countryId)){
            $countryCode = Mage::getModel('directory/country')->load($countryId)->getIso2Code();
            $arrCompany['countryCode'] = $countryCode;
        }



        if(isset($additionalData['kvknummer']) && !empty($additionalData['kvknummer'])){
            $arrCompany['cocNumber'] = $additionalData['kvknummer'];
        }

        if(!empty($arrCompany)){
            $arrStartData['company'] = $arrCompany;
        }
        if(!is_null($optionSubId)){
            $arrStartData['bank'] = $optionSubId;
        }
        if (isset($additionalData['valid_days'])) {
            $arrStartData['expireDate']  = date('d-m-Y H:i:s', strtotime('+' . $additionalData['valid_days'] . ' days'));
        }

        if($sendOrderData){
            $arrStartData['enduser'] = $this->getEnduserData($order);
            $arrStartData['address'] = $this->getShippingAddress($order);
            $arrStartData['invoiceAddress'] = $this->getBillingAddress($order);
            $arrStartData['products'] = $this->getProducts($order);
        }

        return $arrStartData;
    }

    public function startPayment(Mage_Sales_Model_Order $order)
    {
        $store = $order->getStore();
        $this->helperData->loginSDK($store);

        Mage::log('Starting payment for order: ' . $order->getId(), null, 'paynl.log');

        $arrStartData = $this->getTransactionStartData($order);

        try {
            Mage::log('Calling Pay api to start transaction', null, 'paynl.log');

            $objStartResult = \Paynl\Transaction::start($arrStartData);

        } catch (Exception $e) {
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

            return array('url' => Mage::getUrl('checkout/cart'));
        }

        $transaction = Mage::getModel('pay_payment/transaction');

        $transactionId = $objStartResult->getTransactionId();

        Mage::log('Transaction started, transactionId: ' . $transactionId, null, 'paynl.log');

        $transaction->setData(
            array(
                'transaction_id' => $transactionId,
                'service_id' => \Paynl\Config::getServiceId(),
                'option_id' => $this->getPaymentOptionId(),
                'option_sub_id' => null,
                'amount' => round($arrStartData['amount'] * 100),
                'order_id' => $order->getId(),
                'status' => Pay_Payment_Model_Transaction::STATE_PENDING,
                'created' => time(),
                'last_update' => time(),
            ));

        $transaction->save();

        //redirecten
        $url = $objStartResult->getRedirectUrl();

        $payment = $order->getPayment();

        $order->addStatusHistoryComment(
            'Transactie gestart, transactieId: ' . $transactionId . " \nBetaalUrl: " . $url
        );

        $order->save();

        $sendMail = Mage::getStoreConfig('payment/' . $payment->getMethod() . '/send_mail', $order->getStore());

        if ($sendMail == 'start') {
            $order->sendNewOrderEmail();
        }
        return array(
            'url' => $url,
            'transactionId' => $transactionId
        );
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

    public function getPaymentOptionId()
    {
        return $this->_paymentOptionId;
    }

//    /**
//     * Instantiate state and set it to state object
//     * @param string $paymentAction
//     * @param Varien_Object
//     */

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
}