<?php

/**
 * @category   Pay
 * @package    Pay_Payment
 */
class Pay_Payment_Model_Sales_Quote_Address_Total_Paymentcharge extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    public function __construct() {
        $this->setCode('payment_charge');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address) {
        $address->setPaynlPaymentCharge(0);
        $address->setPaynlBasePaymentCharge(0);

        $storeId = $address->getQuote()->getStoreId();
        $store = $address->getQuote()->getStore();

        $items = $address->getAllItems();
        if (!count($items)) {
            return $this;
        }

        $paymentMethod = $address->getQuote()->getPayment()->getMethod();
        $quote = $address->getQuote();

        if ($paymentMethod && substr($paymentMethod, 0, 11) == 'pay_payment') {
            $baseAmount = Mage::helper('pay_payment')->getPaymentCharge($paymentMethod, $address->getQuote());
            $amount = Mage::helper('directory')->currencyConvert($baseAmount, Mage::app()->getWebsite()->getConfig('currency/options/default'), $store->getCurrentCurrencyCode());

            $address->setPaynlPaymentCharge($amount);
            $address->setPaynlBasePaymentCharge($baseAmount);

            $taxClass = Mage::helper('pay_payment')->getPaymentChargeTaxClass($paymentMethod);

            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest($quote->getShippingAddress(), $quote->getBillingAddress(), null, $storeId);
            $request->setStore(Mage::app()->getStore());
            $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClass));

            //$rate = 21;
            if ($rate > 0) {
//                $includesTax = Mage::getStoreConfig('tax/calculation/price_includes_tax');
                $baseChargeTax = round($address->getPaynlBasePaymentCharge() / (1+($rate / 100)) * ($rate/100), 2);
                $chargeTax = round($address->getPaynlPaymentCharge() / (1+($rate / 100)) * ($rate/100), 2);
            } else {
                $baseChargeTax = 0;
                $chargeTax = 0;
            }

            $address->setPaynlPaymentChargeTaxAmount($chargeTax);
            $address->setPaynlBasePaymentChargeTaxAmount($baseChargeTax);

            $rates = array();
            $applied = false;
            foreach ($address->getAppliedTaxes() as $arrRate) {
                // maximaal 1 keer de btw voor de extra kosten toevoegen
                if($arrRate['percent'] == $rate && !$applied){
                    $applied = true;
                    $arrRate['amount'] = $arrRate['amount'] + $chargeTax;
                    $arrRate['base_amount'] = $arrRate['base_amount'] + $baseChargeTax;
                }
              $rates[$arrRate['id']] = $arrRate;
            }


            $address->setAppliedTaxes($rates);

            $address->setBaseTaxAmount($address->getBaseTaxAmount() + $baseChargeTax);
            $address->setTaxAmount($address->getTaxAmount() + $chargeTax);

            $address->setGrandTotal($address->getGrandTotal() + $address->getPaynlPaymentCharge());
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getPaynlBasePaymentCharge());

        }
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {

        $amount = $address->getPaynlPaymentCharge();

        $store = $address->getQuote()->getStore();

        if ($store->getConfig('pay_payment/general/include_tax_payment_charge') == 0) {

            $paymentMethod = $address->getQuote()->getPayment()->getMethod();
            $quote = $address->getQuote();

            $storeId = $address->getQuote()->getStoreId();

            $taxClass = Mage::helper('pay_payment')->getPaymentChargeTaxClass($paymentMethod);

            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest($quote->getShippingAddress(), $quote->getBillingAddress(), null, $storeId);
            $request->setStore(Mage::app()->getStore());
            $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClass));

            $tax_amount = round($amount / (1 + ($rate / 100)) * ($rate / 100), 2);
            $amount     = $amount - $tax_amount;
        }

        if (($amount != 0)) {
            $address->addTotal(array(
                'code' => $this->getCode(),
                'title' => Mage::getStoreConfig('pay_payment/general/text_payment_charge', Mage::app()->getStore()),
                'full_info' => array(),
                'value' => $amount,
                'base_value' => $amount
            ));
        }
        return $amount;
    }

}
