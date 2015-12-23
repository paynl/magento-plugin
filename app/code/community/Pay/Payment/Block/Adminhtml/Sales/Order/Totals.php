<?php

/**
 * Adminhtml order totals block
 *
 * @category    Pay
 * @package     Pay_Payment
 */
class Pay_Payment_Block_Adminhtml_Sales_Order_Totals extends Pay_Payment_Block_Adminhtml_Sales_Totals
{
	/**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $this->_totals['paid'] = new Varien_Object(array(
            'code'      => 'paid',
            'strong'    => true,
            'value'     => $this->getSource()->getTotalPaid(),
            'base_value'=> $this->getSource()->getBaseTotalPaid(),
            'label'     => $this->helper('sales')->__('Total Paid'),
            'area'      => 'footer'
        ));
        $this->_totals['refunded'] = new Varien_Object(array(
            'code'      => 'refunded',
            'strong'    => true,
            'value'     => $this->getSource()->getTotalRefunded(),
            'base_value'=> $this->getSource()->getBaseTotalRefunded(),
            'label'     => $this->helper('sales')->__('Total Refunded'),
            'area'      => 'footer'
        ));
        $this->_totals['due'] = new Varien_Object(array(
            'code'      => 'due',
            'strong'    => true,
            'value'     => $this->getSource()->getTotalDue(),
            'base_value'=> $this->getSource()->getBaseTotalDue(),
            'label'     => $this->helper('sales')->__('Total Due'),
            'area'      => 'footer'
        ));
        return $this;
    }
}
