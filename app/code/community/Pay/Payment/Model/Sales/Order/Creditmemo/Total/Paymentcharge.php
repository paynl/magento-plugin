<?php

class Pay_Payment_Model_Sales_Order_Creditmemo_Total_Paymentcharge extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
  public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
  {
    $creditmemo->setPaymentCharge(0);
    $creditmemo->setBasePaymentCharge(0);

    $paymentCharge = $creditmemo->getOrder()->getPaymentCharge();
    $basePaymentCharge = $creditmemo->getOrder()->getBasePaymentCharge();

    // we moeten de btw meenemen in de berekening
    $paymentMethod = $creditmemo->getOrder()->getPayment()->getMethod();
    $taxClass = Mage::helper('pay_payment')->getPaymentChargeTaxClass($paymentMethod);

    $storeId = Mage::app()->getStore()->getId();

    $taxCalculationModel = Mage::getSingleton('tax/calculation');
    $request = $taxCalculationModel->getRateRequest($creditmemo->getOrder()->getShippingAddress(), $creditmemo->getOrder()->getBillingAddress(), null, $storeId);
    $request->setStore(Mage::app()->getStore());
    $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClass));


    if ($rate > 0)
    {
      $baseChargeTax = round($creditmemo->getBasePaymentCharge() / (1 + ($rate / 100)) * ($rate / 100), 2);
      $chargeTax = round($creditmemo->getPaymentCharge() / (1 + ($rate / 100)) * ($rate / 100), 2);
    } else
    {
      $baseChargeTax = 0;
      $chargeTax = 0;
    }

    $creditmemo->setPaymentCharge($paymentCharge);
    $creditmemo->setBasePaymentCharge($basePaymentCharge);

    $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $baseChargeTax);
    $creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $chargeTax);

    $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getPaymentCharge());
    $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getBasePaymentCharge());

    return $this;
  }
}