<?php

class Pay_Payment_Model_Sales_Order_Invoice_Total_Paymentcharge extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{

    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();

        $paymentCharge = $order->getPaynlPaymentCharge();
        $basePaymentCharge = $order->getPaynlBasePaymentCharge();

        $invoice->setPaynlPaymentCharge($paymentCharge);
        $invoice->setPaynlBasePaymentCharge($basePaymentCharge);

        $paymentChargeTaxAmount = $order->getPaynlPaymentChargeTaxAmount();
        $basePaymentChargeTaxAmount = $order->getPaynlBasePaymentChargeTaxAmount();

        $invoice->setPaynlPaymentChargeTaxAmount($paymentChargeTaxAmount);
        $invoice->setPaynlBasePaymentChargeTaxAmount($basePaymentChargeTaxAmount);

        $invoiceTaxAmount = $invoice->getTaxAmount()*1;
        $invoiceBaseTaxAmount = $invoice->getBaseTaxAmount()*1;
        $taxAmount = $paymentChargeTaxAmount+$invoiceBaseTaxAmount;
        $baseTaxAmount = $basePaymentChargeTaxAmount+$invoiceTaxAmount;

        $invoice->setTaxAmount($taxAmount);
        $invoice->setBaseTaxAmount($baseTaxAmount);

        $invoice->setGrandTotal($invoice->getGrandTotal()+$paymentCharge);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal()+$basePaymentCharge);

        return $this;
    }

}
