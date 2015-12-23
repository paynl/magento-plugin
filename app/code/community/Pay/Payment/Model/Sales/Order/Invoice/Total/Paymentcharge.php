<?php

class Pay_Payment_Model_Sales_Order_Invoice_Total_Paymentcharge extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{

    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $invoice->setPaymentCharge(0);
        $invoice->setBasePaymentCharge(0);

        $paymentCharge = $invoice->getOrder()->getPaymentCharge();
        $basePaymentCharge = $invoice->getOrder()->getBasePaymentCharge();

        // we moeten de btw meenemen in de berekening
        $paymentMethod = $invoice->getOrder()->getPayment()->getMethod();
        $taxClass = Mage::helper('pay_payment')->getPaymentChargeTaxClass($paymentMethod);

        $storeId = Mage::app()->getStore()->getId();

        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $request = $taxCalculationModel->getRateRequest($invoice->getOrder()->getShippingAddress(), $invoice->getOrder()->getBillingAddress(), null, $storeId);
        $request->setStore(Mage::app()->getStore());
        $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClass));

        if ($rate > 0)
        {
            $baseChargeTax = round($invoice->getBasePaymentCharge() / (1 + ($rate / 100)) * ($rate / 100), 2);
            $chargeTax = round($invoice->getPaymentCharge() / (1 + ($rate / 100)) * ($rate / 100), 2);
        } else
        {
            $baseChargeTax = 0;
            $chargeTax = 0;
        }

        $invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + $baseChargeTax);
        $invoice->setTaxAmount($invoice->getTaxAmount() + $chargeTax);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getPaymentCharge());
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getBasePaymentCharge());


        $invoice->setPaymentCharge($paymentCharge);
        $invoice->setBasePaymentCharge($basePaymentCharge);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getPaymentCharge());
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getBasePaymentCharge());


        return $this;
    }

}
