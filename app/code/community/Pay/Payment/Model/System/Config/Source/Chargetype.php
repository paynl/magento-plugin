<?php 

/**
 * @category   Pay
 * @package    Pay_Payment
 */
class Pay_Payment_Model_System_Config_Source_Chargetype extends Varien_Object
{
    public function toOptionArray()
    { 
    	 return array(
            array('value'=>'fixed', 'label'=>Mage::helper('adminhtml')->__('Vast bedrag')),
            array('value'=>'percentage', 'label'=>Mage::helper('adminhtml')->__('Percentage van totaalbedrag')),
        );   
    }   
}