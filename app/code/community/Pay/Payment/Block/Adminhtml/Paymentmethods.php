<?php

class Pay_Payment_Block_Adminhtml_Paymentmethods extends Mage_Adminhtml_Block_System_Config_Form_Field
{

  protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
  {
    $form = $element->getForm();
    $parent = $form->getParent();
    $scope = $parent->getScope();
    $scopeId = $parent->getScopeId();

    if ($scope == 'stores')
    {
      $store = Mage::app()->getStore($scopeId);
    } elseif ($scope == 'websites')
    {
      $store = Mage::app()->getWebsite($scopeId);
    } else
    {
      $store = Mage::app()->getStore(0);
    }


    try
    {
      $helper = Mage::helper('pay_payment');

      $helper->loadOptions($store);

      $arrOptions = $helper->getOptions($store);


      $output = '';
      foreach ($arrOptions as $option)
      {
        $output .= '<img src="' . $option->getImage() . '" /> ' . $option->getName() . '<br />';
      }
    } catch (Exception $e)
    {
      $output = $e->getMessage();
    }

    return '<div>' . $output . '</div>';
  }

}
