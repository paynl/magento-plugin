<?php

class Pay_Payment_Model_Sales_Order_Creditmemo_Total_Paymentcharge extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $store = $order->getStore();

        $exclude_fee_refund = $store->getConfig('pay_payment/general/exclude_fee_refund');
        if($exclude_fee_refund) {
            return  $this;
        }

        $paymentCharge = $order->getPaynlPaymentCharge();
        $basePaymentCharge = $order->getPaynlBasePaymentCharge();
        $creditmemo->setPaynlPaymentCharge($paymentCharge);
        $creditmemo->setPaynlBasePaymentCharge($basePaymentCharge);

        $paymentChargeTax = $order->getPaynlPaymentChargeTaxAmount();
        $baseBaymentChargeTax = $order->getPaynlBasePaymentChargeTaxAmount();
        $creditmemo->setPaynlPaymentChargeTaxAmount($paymentChargeTax);
        $creditmemo->setPaynlBasePaymentChargeTaxAmount($baseBaymentChargeTax);

        $creditmemo->setTaxAmount(($creditmemo->getTaxAmount()*1) + $paymentChargeTax);
        $creditmemo->setBaseTaxAmount(($creditmemo->getBaseTaxAmount()*1) + $baseBaymentChargeTax);

        $creditmemo->setGrandTotal(($creditmemo->getGrandTotal()*1) + $paymentCharge);
        $creditmemo->setBaseGrandTotal(($creditmemo->getBaseGrandTotal()*1) + $basePaymentCharge);

        return $this;
    }
}