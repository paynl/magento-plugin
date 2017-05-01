<?php

/**
 * Adminhtml order invoice totals block
 *
 * @category    Pay
 * @package     Paay_Payment
 */
class Pay_Payment_Block_Adminhtml_Sales_Order_Invoice_Totals extends Mage_Adminhtml_Block_Sales_Order_Invoice_Totals
{
    protected $_invoice = null;

    public function getInvoice()
    {
        if ($this->_invoice === null) {
            if ($this->hasData('invoice')) {
                $this->_invoice = $this->_getData('invoice');
            } elseif (Mage::registry('current_invoice')) {
                $this->_invoice = Mage::registry('current_invoice');
            } elseif ($this->getParentBlock()->getInvoice()) {
                $this->_invoice = $this->getParentBlock()->getInvoice();
            }
        }
        return $this->_invoice;
    }
    
    public function getSource()
    {
        return $this->getInvoice();
    }

    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        parent::_initTotals();

        /** @var Mage_Sales_Model_Order $source */
        $source = $this->getSource();

        $totals = $this->_totals;
        $newTotals = array();
        if (count($totals)>0) {
            foreach ($totals as $index=>$arr) {
                if ($index == "grand_total") {
                    if (((float)$this->getSource()->getPaymentCharge()) != 0) {
                        $label = Mage::getStoreConfig('pay_payment/general/text_payment_charge', Mage::app()->getStore());
                        $newTotals['payment_charge'] = new Varien_Object(array(
                            'code'  => 'payment_charge',
                            'field' => 'payment_charge',
                            'base_value' => $source->getBasePaymentCharge(),
                            'value' => $source->getPaymentCharge(),
                            'label' => $label
                        ));
                    }
                }
                $newTotals[$index] = $arr;
            }
            $this->_totals = $newTotals;
        }

        return $this;
    }
}
