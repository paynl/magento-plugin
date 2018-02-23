<?php

class Pay_Payment_Model_Source_Language {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {

        return array(
            array('value' => 'nl', 'label' => Mage::helper('core')->__('Nederlands')),
            array('value' => 'en', 'label' => Mage::helper('core')->__('Engels')),
            array('value' => 'de', 'label' => Mage::helper('core')->__('Duits')),
            array('value' => 'es', 'label' => Mage::helper('core')->__('Spaans')),
            array('value' => 'it', 'label' => Mage::helper('core')->__('Italiaans')),
            array('value' => 'fr', 'label' => Mage::helper('core')->__('Frans')),
            array('value' => 'da', 'label' => Mage::helper('core')->__('Deens')),
            array('value' => 'no', 'label' => Mage::helper('core')->__('Noors')),
            array('value' => 'pt', 'label' => Mage::helper('core')->__('Portugees')),
            array('value' => 'ru', 'label' => Mage::helper('core')->__('Russisch')),
            array('value' => 'sv', 'label' => Mage::helper('core')->__('Zweeds')),
            array('value' => 'tr', 'label' => Mage::helper('core')->__('Turks')),
            array('value' => 'browser', 'label' => Mage::helper('core')->__('Browser taal gebruiken')),
        );
    }

}
