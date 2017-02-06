<?php

class Pay_Payment_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract
{

    const OPTION_ID = 0;

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    protected $_paymentOptionName;

    protected $_additionalData = array();

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

        $serviceId = Mage::getStoreConfig('pay_payment/general/serviceid', $store);
        $apiToken = Mage::getStoreConfig('pay_payment/general/apitoken', $store);

        $useBackupApi = Mage::getStoreConfig('pay_payment/general/use_backup_api', $store);
        $backupApiUrl = Mage::getStoreConfig('pay_payment/general/backup_api_url', $store);
        if ($useBackupApi == 1) {
            Pay_Payment_Helper_Api::_setBackupApiUrl($backupApiUrl);
        }

        $parentTransactionId = $payment->getParentTransactionId();

        $apiRefund = Mage::helper('pay_payment/api_refund');
        $apiRefund instanceof Pay_Payment_Helper_Api_Refund;
        $apiRefund->setApiToken($apiToken);
        $apiRefund->setServiceId($serviceId);

        $apiRefund->setTransactionId($parentTransactionId);
        $amount = (int)round($amount * 100);
        $apiRefund->setAmount($amount);

        $apiRefund->doRequest();

        return $this;
    }

    public function startPayment(Mage_Sales_Model_Order $order)
    {
        $session = Mage::getSingleton('checkout/session');

        Mage::log('Starting payment for order: ' . $order->getId(), null, 'paynl.log');

        $payment = $order->getPayment();

        $additionalData = $session->getPaynlPaymentData();

        /** @var Pay_Payment_Helper_Data $helper */
        $helper = Mage::helper('pay_payment');

        $optionId = $this->_paymentOptionId;
        $optionSubId = $additionalData['option_sub'] ? $additionalData['option_sub'] : null;
        $iban = $additionalData['iban'] ? $additionalData['iban'] : null;

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
        list($dobYear, $dobMonth, $dobDay) = explode('-', $birthDate);

        $birthDate = $dobDay . '-' . $dobMonth . '-' . $dobYear;

        $serviceId = Mage::getStoreConfig('pay_payment/general/serviceid', Mage::app()->getStore());

        $apiToken = Mage::getStoreConfig('pay_payment/general/apitoken', Mage::app()->getStore());
        $useBackupApi = Mage::getStoreConfig('pay_payment/general/use_backup_api', Mage::app()->getStore());
        $backupApiUrl = Mage::getStoreConfig('pay_payment/general/backup_api_url', Mage::app()->getStore());
        if ($useBackupApi == 1) {
            Pay_Payment_Helper_Api::_setBackupApiUrl($backupApiUrl);
        }

        $amount = $order->getGrandTotal();

        $sendOrderData = Mage::getStoreConfig('pay_payment/general/send_order_data', Mage::app()->getStore());

        $api = Mage::helper('pay_payment/api_start');
        /* @var $api Pay_Payment_Helper_Api_Start */

        if (isset($additionalData['valid_days'])) {
            $api->setExpireDate(date('d-m-Y H:i:s', strtotime('+' . $additionalData['valid_days'] . ' days')));
        }

        $api->setExtra2($order->getCustomerEmail());

        if ($sendOrderData == 1) {
            $items = $order->getItemsCollection(array(), true);
            foreach ($items as $item) {
                /* @var $item Mage_Sales_Model_Order_Item */
                $productId = $item->getId();
                $description = $item->getName();
                $price = $item->getPriceInclTax();
                $taxAmount = $item->getTaxAmount();
                $quantity = $item->getQtyOrdered();

                if ($price != 0) {
                    $taxClass = $helper->calculateTaxClass($price, $taxAmount / $quantity);
                    $price = round($price * 100);
                    $api->addProduct($productId, $description, $price, $quantity, $taxClass);
                }

            }

            $discountAmount = $order->getDiscountAmount();

            if ($discountAmount < 0) {
                $api->addProduct('discount', 'Korting (' . $order->getDiscountDescription() . ')', round($discountAmount * 100), 1, 'N', 'DISCOUNT');
            }

            $shipping = $order->getShippingInclTax();

            if ($shipping > 0) {
                $shippingTax = $order->getShippingTaxAmount();
                $shippingTaxClass = $helper->calculateTaxClass($shipping, $shippingTax);
                $shipping = round($shipping * 100);
                if ($shipping != 0) {
                    $api->addProduct('shipping', 'Verzendkosten', $shipping, 1, $shippingTaxClass, 'SHIPPING');
                }
            }

            $extraFee = $order->getPaymentCharge();
            if ($extraFee != 0) {
                $code = $payment->getMethod();
                $taxClass = $helper->getPaymentChargeTaxClass($code);

                $taxCalculationModel = Mage::getSingleton('tax/calculation');
                $request = $taxCalculationModel->getRateRequest($order->getShippingAddress(), $order->getBillingAddress());
                $request->setStore(Mage::app()->getStore());
                $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClass));

                $taxCode = $helper->getTaxCodeFromRate($rate);

                $api->addProduct('paymentfee', Mage::getStoreConfig('pay_payment/general/text_payment_charge', Mage::app()->getStore()), round($extraFee * 100), 1, $taxCode, 'PAYMENT');
            }

            $arrEnduser = array();
            $shippingAddress = $order->getShippingAddress();

            $arrEnduser['gender'] = substr($order->getCustomerGender(), 0, 1);

            if (isset($iban)) {
                $arrEnduser['iban'] = strtoupper($iban);
            }

            $arrEnduser['dob'] = $birthDate;
            $arrEnduser['emailAddress'] = $order->getCustomerEmail();
            $billingAddress = $order->getBillingAddress();

            if (!empty($shippingAddress)) {
//                $arrEnduser['initials'] = substr($shippingAddress->getFirstname(), 0, 1);
                $arrEnduser['initials'] = $shippingAddress->getFirstname();
                $arrEnduser['lastName'] = substr($shippingAddress->getLastname(), 0, 30);

                $arrEnduser['phoneNumber'] = substr($shippingAddress->getTelephone(), 0, 30);

                $arrAddressFull = array();
                $arrAddressFull[] = $shippingAddress->getStreet1();
                $arrAddressFull[] = $shippingAddress->getStreet2();
                $arrAddressFull[] = $shippingAddress->getStreet3();
                $arrAddressFull[] = $shippingAddress->getStreet4();
                $arrAddressFull = array_unique($arrAddressFull);
                $addressFull = implode(' ', $arrAddressFull);


                $addressFull = str_replace("\n", ' ', $addressFull);
                $addressFull = str_replace("\r", ' ', $addressFull);

                list($address, $housenumber) = $helper->splitAddress($addressFull);

                $arrEnduser['address']['streetName'] = $address;
                $arrEnduser['address']['streetNumber'] = $housenumber;
                $arrEnduser['address']['zipCode'] = $shippingAddress->getPostcode();
                $arrEnduser['address']['city'] = $shippingAddress->getCity();
                $arrEnduser['address']['countryCode'] = $shippingAddress->getCountry();
            } elseif (!empty($billingAddress)) {
                $arrEnduser['initials'] = substr($billingAddress->getFirstname(), 0, 1);
                $arrEnduser['lastName'] = substr($billingAddress->getLastname(), 0, 30);
            }

            if (!empty($billingAddress)) {
                $arrAddressFull = array();
                $arrAddressFull[] = $billingAddress->getStreet1();
                $arrAddressFull[] = $billingAddress->getStreet2();
                $arrAddressFull[] = $billingAddress->getStreet3();
                $arrAddressFull[] = $billingAddress->getStreet4();
                $arrAddressFull = array_unique($arrAddressFull);
                $addressFull = implode(' ', $arrAddressFull);

                $addressFull = str_replace("\n", ' ', $addressFull);
                $addressFull = str_replace("\r", ' ', $addressFull);

                list($address, $housenumber) = $helper->splitAddress($addressFull);

                $arrEnduser['invoiceAddress']['streetName'] = $address;
                $arrEnduser['invoiceAddress']['streetNumber'] = $housenumber;
                $arrEnduser['invoiceAddress']['zipCode'] = $billingAddress->getPostcode();
                $arrEnduser['invoiceAddress']['city'] = $billingAddress->getCity();
                $arrEnduser['invoiceAddress']['countryCode'] = $billingAddress->getCountry();

//                $arrEnduser['invoiceAddress']['initials'] = substr($billingAddress->getFirstname(), 0, 1);
                $arrEnduser['invoiceAddress']['initials'] = $billingAddress->getFirstname();
                $arrEnduser['invoiceAddress']['lastName'] = substr($billingAddress->getLastname(), 0, 30);
            }
            $api->setEnduser($arrEnduser);
        }

        $api->setServiceId($serviceId);
        $api->setApiToken($apiToken);

        $api->setAmount(round($amount * 100));
        $api->setCurrency($order->getOrderCurrencyCode());

        $api->setPaymentOptionId($optionId);
        $api->setFinishUrl(Mage::getUrl('pay_payment/order/return'));

        $api->setExchangeUrl(Mage::getUrl('pay_payment/order/exchange'));
        $api->setOrderId($order->getIncrementId());

        if (!empty($optionSubId)) {
            $api->setPaymentOptionSubId($optionSubId);
        }
        try {
            Mage::log('Calling Pay api to start transaction', null, 'paynl.log');

            $resultData = $api->doRequest();

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
            // Mage::getSingleton('checkout/session')->addError(print_r($api->getPostData(),1));
            // Redirect via header

            return array('url' => Mage::getUrl('checkout/cart'));
        }

        $transaction = Mage::getModel('pay_payment/transaction');

        $transactionId = $resultData['transaction']['transactionId'];

        Mage::log('Transaction started, transactionId: ' . $transactionId, null, 'paynl.log');

        $transaction->setData(
            array(
                'transaction_id' => $transactionId,
                'service_id' => $serviceId,
                'option_id' => $optionId,
                'option_sub_id' => $optionSubId,
                'amount' => round($amount * 100),
                'order_id' => $order->getId(),
                'status' => Pay_Payment_Model_Transaction::STATE_PENDING,
                'created' => time(),
                'last_update' => time(),
            ));

        $transaction->save();

        //redirecten
        $url = $resultData['transaction']['paymentURL'];

        $statusPending = Mage::getStoreConfig('payment/' . $payment->getMethod() . '/order_status', Mage::app()->getStore());

        $order->addStatusHistoryComment(
            'Transactie gestart, transactieId: ' . $transactionId . " \nBetaalUrl: " . $url
        );


        $order->save();

        $sendMail = Mage::getStoreConfig('payment/' . $payment->getMethod() . '/send_mail', Mage::app()->getStore());
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