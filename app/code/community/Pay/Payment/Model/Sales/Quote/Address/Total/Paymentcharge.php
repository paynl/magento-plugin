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
        $address->setPaymentCharge(0);
        $address->setBasePaymentCharge(0);

        $storeId = Mage::app()->getStore()->getId();
        
        $items = $address->getAllItems();
        if (!count($items)) {
            return $this;
        }

        $paymentMethod = $address->getQuote()->getPayment()->getMethod();
        $quote = $address->getQuote();
   
        if ($paymentMethod && substr($paymentMethod, 0, 11) == 'pay_payment') {
            $baseAmount = Mage::helper('pay_payment')->getPaymentCharge($paymentMethod, $address->getQuote());
            $amount = Mage::helper('directory')->currencyConvert($baseAmount, Mage::app()->getWebsite()->getConfig('currency/options/default'), Mage::app()->getStore()->getCurrentCurrencyCode());

            $address->setPaymentCharge($amount);
            $address->setBasePaymentCharge($baseAmount);
          
            $taxClass = Mage::helper('pay_payment')->getPaymentChargeTaxClass($paymentMethod);

            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest($quote->getShippingAddress(), $quote->getBillingAddress(), null, $storeId);
            $request->setStore(Mage::app()->getStore());
            $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClass));
            
            //$rate = 21;
            if ($rate > 0) {
//                $includesTax = Mage::getStoreConfig('tax/calculation/price_includes_tax');   
                
                $baseChargeTax = round($address->getBasePaymentCharge() / (1+($rate / 100)) * ($rate/100), 2);
                $chargeTax = round($address->getPaymentCharge() / (1+($rate / 100)) * ($rate/100), 2);
            } else {
                $baseChargeTax = 0;
                $chargeTax = 0;
            }
            
            $rates = array();
            $applied = false;
            foreach ($address->getAppliedTaxes() as $arrRate) {
                // maximaal 1 keer de btw voor de extra kosten toevoegen
                if($arrRate['percent'] == $rate && !$applied){
                    $applied = true;
                    $arrRate['amount'] = $arrRate['amount'] + $chargeTax;
                    $arrRate['base_amount'] = $arrRate['base_amount'] + $baseChargeTax;
                }
              $rates[] = $arrRate;
            }
            

            $address->setAppliedTaxes($rates);

            $address->setBaseTaxAmount($address->getBaseTaxAmount() + $baseChargeTax);
            $address->setTaxAmount($address->getTaxAmount() + $chargeTax);

            $address->setGrandTotal($address->getGrandTotal() + $address->getPaymentCharge());
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBasePaymentCharge());
       
        }
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {

        $amount = $address->getPaymentCharge();

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
