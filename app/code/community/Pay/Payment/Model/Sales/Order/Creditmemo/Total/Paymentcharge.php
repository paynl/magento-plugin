<?php

class Pay_Payment_Model_Sales_Order_Creditmemo_Total_Paymentcharge extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $paymentCharge = $order->getPaymentCharge();
        $basePaymentCharge = $order->getBasePaymentCharge();
        $creditmemo->setPaymentCharge($paymentCharge);
        $creditmemo->setBasePaymentCharge($basePaymentCharge);

        $paymentChargeTax = $order->getPaymentChargeTaxAmount();
        $baseBaymentChargeTax = $order->getBasePaymentChargeTaxAmount();
        $creditmemo->setPaymentChargeTaxAmount($paymentChargeTax);
        $creditmemo->setBasePaymentChargeTaxAmount($baseBaymentChargeTax);

        $creditmemo->setTaxAmount(($creditmemo->getTaxAmount()*1) + $paymentChargeTax);
        $creditmemo->setBaseTaxAmount(($creditmemo->getBaseTaxAmount()*1) + $baseBaymentChargeTax);

        $creditmemo->setGrandTotal(($creditmemo->getGrandTotal()*1) + $paymentCharge);
        $creditmemo->setBaseGrandTotal(($creditmemo->getBaseGrandTotal()*1) + $basePaymentCharge);

        return $this;
    }
}