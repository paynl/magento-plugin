<?php

class Pay_Payment_Helper_Total extends Mage_Core_Helper_Abstract {

    public function addToBlock($block) {
        $order = $block->getOrder();
        $info = $order->getPayment()->getMethodInstance()->getInfoInstance();

        $label = Mage::getStoreConfig('payment/' . $order->getPayment()->getMethod() . '/surcharge_label');

//        $paymentFee = $info->getAdditionalInformation('invoice_fee');
//        $basePaymentFee = $info->getAdditionalInformation('base_invoice_fee');
//
//        $paymentFeeExcludingVat = $info->getAdditionalInformation('invoice_fee_exluding_vat');
//        $basePaymentFeeExcludingVat = $info->getAdditionalInformation('base_invoice_fee_exluding_vat');

        
        $fee = new Varien_Object();
        $fee->setCode('surcharge_incl');
        
        $fee->setLabel($label);
        $fee->setBaseValue(10);
        $fee->setValue(11);
        $block->addTotalBefore($fee, 'shipping');


        return $block;
    }

}
