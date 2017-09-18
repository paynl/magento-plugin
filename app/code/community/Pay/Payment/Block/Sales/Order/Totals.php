<?php

/**
 * @category    Pay
 * @package     Pay_Payment
 */
class Pay_Payment_Block_Sales_Order_Totals extends Mage_Sales_Block_Order_Totals
{
  /**
   * Initialize order totals array
   *
   * @return Mage_Sales_Block_Order_Totals
   */
  protected function _initTotals()
  {
    parent::_initTotals();

    $source = $this->getSource();

    /**
     * Add store rewards
     */
    $totals = $this->_totals;
    $newTotals = array();
    if (count($totals) > 0)
    {
      foreach ($totals as $index => $arr)
      {
        if ($index == "grand_total")
        {
          if (((float)$this->getSource()->getPaymentCharge()) != 0)
          {
            $label = Mage::getStoreConfig('pay_payment/general/text_payment_charge', Mage::app()->getStore());
            $newTotals['payment_charge'] = new Varien_Object(array(
              'code' => 'paynl_payment_charge',
              'field' => 'paynl_payment_charge',
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
