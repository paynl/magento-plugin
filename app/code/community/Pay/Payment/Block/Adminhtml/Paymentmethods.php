<?php

class Pay_Payment_Block_Adminhtml_Paymentmethods extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {       
        $form = $element->getForm();
        $parent = $form->getParent();
        $scope = $parent->getScope();
        $scopeId = $parent->getScopeId();
        
        if($scope == 'stores'){
            $store = Mage::app()->getStore($scopeId);
        } elseif($scope == 'websites') {
            $store = Mage::app()->getWebsite($scopeId);
        } else {
            $store = Mage::app()->getStore(0);
        }
        
     
//        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) { // store level
//            $store_id = Mage::getModel('core/store')->load($code)->getId();
//            $store = Mage::app()->getStore($store_id);
//        } elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) { // website level
//            $website_id = Mage::getModel('core/website')->load($code)->getId();
//            $store = Mage::app()->getWebsite($website_id);
//            //$store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
//            
//        } else { // default level
//            $store_id = 0;
//            $store = Mage::getModel('core/store')->load($code)->getId();
//        }
//        //$store = Mage::app()->getStore($store_id);
//   

        try {
            $helper = Mage::helper('pay_payment');

            $helper->loadOptions($store);

            $arrOptions = $helper->getOptions($store);
            
            
            $output = '';
            $arrOptionNames = array();
            foreach ($arrOptions as $option) {
                $output .= '<img src="' . $option->getImage() . '" /> ' . $option->getName() . '<br />';
//                foreach ($option->getSubs() as $sub) {
//                    $output .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="' . $sub->getImage() . '" /><br />';
//                }
            }
        } catch (Exception $e) {
            $output = $e->getMessage();
        }

        return '<div>' . $output . '</div>';
    }

}
