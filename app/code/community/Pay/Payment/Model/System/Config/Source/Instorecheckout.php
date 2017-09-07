<?php 

/**
 * @category   Pay
 * @package    Pay_Payment
 */
class Pay_Payment_Model_System_Config_Source_Instorecheckout extends Varien_Object
{
    public function toOptionArray()
    { 
    	 return array(
            array('value'=>'0', 'label'=>Mage::helper('adminhtml')->__('Nee')),
            array('value'=>'1', 'label'=>Mage::helper('adminhtml')->__('Ja')),
            array('value'=>'2', 'label'=>Mage::helper('adminhtml')->__('Ja, aleen vanaf bepaalde ip adressen')),
        );
    }   
}