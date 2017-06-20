<?php
class Pay_Payment_Block_Adminhtml_Version extends Mage_Adminhtml_Block_System_Config_Form_Field{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return (string) Mage::helper('pay_payment')->getVersion();
    }
}