<?php
class Pay_Payment_Model_Source_Paymentmethod_Active {
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('pay_payment');
        
        $strStore = Mage::app()->getRequest()->getParam('store');
        $strWebsite = Mage::app()->getRequest()->getParam('website');
        
        if(strlen($strStore)){
            $store = Mage::app()->getStore($strStore);
        } elseif(strlen($strWebsite)) {
            $store = Mage::app()->getWebsite($strWebsite);
        } else {
            $store = Mage::app()->getStore(0);
        }
        
        $available = $helper->isOptionAvailable($this->_option_id, $store);
      
        if($available){
        return array(
            array('value' => 1, 'label'=>'Ja'),
            array('value' => 0, 'label'=>'Nee'),
        );
        } else {
            return array(
            array('value' => 0, 'label'=>'Nee (niet geactiveerd)'),
        );
        }
    }
   
}