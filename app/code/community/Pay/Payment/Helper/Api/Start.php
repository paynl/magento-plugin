<?php

class Pay_Payment_Helper_Api_Start extends Pay_Payment_Helper_Api {

    protected $_version = 'v3';
    protected $_controller = 'transaction';
    protected $_action = 'start';
    private $_amount;
    private $_currency;
    private $_paymentOptionId;
    private $_paymentOptionSubId;
    private $_finishUrl;
    private $_costs_for_customer = 0;
    private $_exchangeUrl;
    private $_orderId;
    private $_description;
    private $_enduser;
    private $_extra2;
    private $_extra3;
    private $_products = array();

    public function addProduct($id, $description, $price, $quantity, $vatPercentage) {
        if (!is_numeric($price)) {
            throw Mage::exception('Pay_Payment_Helper_Api', 'Price moet numeriek zijn', 1);
        }
        if (!is_numeric($quantity)) {
            throw Mage::exception('Pay_Payment_Helper_Api', 'Quantity moet numeriek zijn', 1);
        }
        $quantity = $quantity * 1;

        //description mag maar 45 chars lang zijn
        $description = substr($description, 0, 45);

        $arrProduct = array(
            'productId' => $id,
            'description' => $description,
            'price' => $price,
            'quantity' => $quantity,
            'vatCode' => $vatPercentage,
        );
        $this->_products[] = $arrProduct;
    }

    public function setEnduser($enduser) {
        $this->_enduser = $enduser;
    }

    public function setAmount($amount) {
        if (is_numeric($amount)) {
            $this->_amount = $amount;
        } else {
            throw Mage::exception('Pay_Payment_Helper_Api', 'Amount is niet numeriek', 1);
        }
    }

    public function setCurrency($currency) {
        $this->_currency = strtoupper($currency);
    }

    public function setPaymentOptionId($paymentOptionId) {
        if (is_numeric($paymentOptionId)) {
            $this->_paymentOptionId = $paymentOptionId;
        } else {
            throw Mage::exception('Pay_Payment_Helper_Api', 'PaymentOptionId is niet numeriek', 1);
        }
    }

    public function setPaymentOptionSubId($paymentOptionSubId) {
        if (is_numeric($paymentOptionSubId)) {
            $this->_paymentOptionSubId = $paymentOptionSubId;
        } else {
            throw Mage::exception('Pay_Payment_Helper_Api', 'PaymentOptionSubId is niet numeriek', 1);
        }
    }

    public function setFinishUrl($finishUrl) {
        $this->_finishUrl = $finishUrl;
    }

    public function setCostsForCustomer($costs_for_customer) {
        if ($costs_for_customer == 1) {
            $this->_costs_for_customer = 1;
        } else {
            $this->_costs_for_customer = 0;
        }
    }

    public function setExchangeUrl($exchangeUrl) {
        $this->_exchangeUrl = $exchangeUrl;
    }

    public function setExtra2($extra2) {
        $this->_extra2 = $extra2;
    }

    public function setExtra3($extra3) {
        $this->_extra3 = $extra3;
    }

    public function setOrderId($orderId) {
        $this->_orderId = $orderId;
    }

    public function setDescription($description) {
        $this->_description = $description;
    }

    protected function _getPostData() {
        $data = parent::_getPostData();

        /*
         * Verplicht
         *
         * amount
         * paymentOptionId
         * finishUrl
         * exchangeUrl
         * orderId
         * 
         * 
         * Optioneel
         * 
         * costs
         * paymentOptionSubId
         */

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
        if (empty($this->_amount)) {
            throw Mage::exception('Pay_Payment_Helper_Api', 'Amount is niet geset', 1);
        } else {
            $data['amount'] = $this->_amount;
        }
        if (!empty($this->_currency)) {
            $data['transaction']['currency'] = $this->_currency;
        }
        if (empty($this->_paymentOptionId)) {
            throw Mage::exception('Pay_Payment_Helper_Api', 'PaymentOptionId is niet geset', 1);
        } else {
            $data['paymentOptionId'] = $this->_paymentOptionId;
        }
        if (empty($this->_finishUrl)) {
            throw Mage::exception('Pay_Payment_Helper_Api', 'FinishUrl is niet geset', 1);
        } else {
            $data['finishUrl'] = $this->_finishUrl;
        }
        if (empty($this->_exchangeUrl)) {
            throw Mage::exception('Pay_Payment_Helper_Api', 'exchangeUrl is niet geset', 1);
        } else {
            $data['transaction']['orderExchangeUrl'] = $this->_exchangeUrl;
        }
        if (empty($this->_orderId)) {
            throw Mage::exception('Pay_Payment_Helper_Api', 'orderId is niet geset', 1);
        } else {
            $data['statsData']['extra1'] = $this->_orderId;
        }
        if (empty($this->_description)) {
            $data['transaction']['description'] = $this->_orderId;
        } else {
            $data['transaction']['description'] = $this->_description;
        }

        if (!empty($this->_extra2)) {
            $data['statsData']['extra2'] = $this->_extra2;
        }
        if (!empty($this->_extra3)) {
            $data['statsData']['extra3'] = $this->_extra3;
        }

        if ($this->_costs_for_customer == 1) {
            $data['transaction']['costs'] = 1;
        }
// $data['transaction']['currency'] = 'EUR';
        if (!empty($this->_paymentOptionSubId)) {
            $data['paymentOptionSubId'] = $this->_paymentOptionSubId;
        }

        //ip en browserdata setten
        $data['ipAddress'] = Pay_Payment_Helper_Data::getIp();
        $data['browserData'] = array(
            'browser_name_regex' => '^mozilla/5\.0 (windows; .; windows nt 5\.1; .*rv:.*) gecko/.* firefox/0\.9.*$',
            'browser_name_pattern' => 'Mozilla/5.0 (Windows; ?; Windows NT 5.1; *rv:*) Gecko/* Firefox/0.9*',
            'parent' => 'Firefox 0.9',
            'platform' => 'WinXP',
            'browser' => 'Firefox',
            'version' => 0.9,
            'majorver' => 0,
            'minorver' => 9,
            'cssversion' => 2,
            'frames' => 1,
            'iframes' => 1,
            'tables' => 1,
            'cookies' => 1,
        );
        if (!empty($this->_products)) {
            $data['saleData']['invoiceDate'] = date('d-m-Y');
            $data['saleData']['deliveryDate'] = date('d-m-Y', strtotime('+1 day'));
            $data['saleData']['orderData'] = $this->_products;
        }
        $data['enduser'] = array();
        if (!empty($this->_enduser)) {
            $data['enduser'] = $this->_enduser;
        }


        //taal instellen

        $data['enduser']['language'] = Pay_Payment_Helper_Data::getLanguage();


        return $data;
    }

}
