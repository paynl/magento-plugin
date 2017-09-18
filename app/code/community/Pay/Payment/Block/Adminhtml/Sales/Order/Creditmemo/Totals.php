<?php

/**
 * Adminhtml order creditmemo totals block
 *
 * @category    Pay
 * @package     Pay_Payment
 */
class Pay_Payment_Block_Adminhtml_Sales_Order_Creditmemo_Totals extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Totals
{
    protected $_creditmemo;

    public function getCreditmemo()
    {
        if ($this->_creditmemo === null) {
            if ($this->hasData('creditmemo')) {
                $this->_creditmemo = $this->_getData('creditmemo');
            } elseif (Mage::registry('current_creditmemo')) {
                $this->_creditmemo = Mage::registry('current_creditmemo');
            } elseif ($this->getParentBlock() && $this->getParentBlock()->getCreditmemo()) {
                $this->_creditmemo = $this->getParentBlock()->getCreditmemo();
            }
        }
        return $this->_creditmemo;
    }

    public function getSource()
    {
        return $this->getCreditmemo();
    }

    /**
     * Initialize creditmemo totals array
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
                    if (((float)$this->getSource()->getPaynlPaymentCharge()) != 0) {
                        $label = Mage::getStoreConfig('pay_payment/general/text_payment_charge', Mage::app()->getStore());
                        $newTotals['paynl_payment_charge'] = new Varien_Object(array(
                            'code'  => 'paynl_payment_charge',
                            'field' => 'paynl_payment_charge',
                            'base_value' => $source->getPaynlBasePaymentCharge(),
                            'value' => $source->getPaynlPaymentCharge(),
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
