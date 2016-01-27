<?php

class Pay_Payment_Helper_Total extends Mage_Core_Helper_Abstract {

    public function addToBlock($block) {
        $order = $block->getOrder();

        $label = Mage::getStoreConfig('payment/' . $order->getPayment()->getMethod() . '/surcharge_label');
        
        $fee = new Varien_Object();
        $fee->setCode('surcharge_incl');
        
        $fee->setLabel($label);
        $fee->setBaseValue(10);
        $fee->setValue(11);
        $block->addTotalBefore($fee, 'shipping');


        return $block;
    }

}
