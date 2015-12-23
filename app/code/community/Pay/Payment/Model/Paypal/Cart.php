<?php

class Pay_Payment_Model_Paypal_Cart extends Mage_Paypal_Model_Cart
{
    protected function _render()
    {

        if (!$this->_shouldRender) {
            return;
        }

        // regular items from the sales entity
        $this->_items = array();
        foreach ($this->_salesEntity->getAllItems() as $item) {
            if (!$item->getParentItem()) {
                $this->_addRegularItem($item);
            }
        }
        end($this->_items);
        $lastRegularItemKey = key($this->_items);

		if(Mage::getStoreConfig('payment/paypal_payment_solutions/charge_type')){
			// Magento 1.7.0.2 and higher
			$chargeType = Mage::getStoreConfig('payment/paypal_payment_solutions/charge_type');
			$chargeValue = Mage::getStoreConfig('payment/paypal_payment_solutions/charge_value');
		} else {
			// Extra fee - added 21-4-12
			$chargeType = Mage::getStoreConfig('paypal/account/charge_type');
			$chargeValue = Mage::getStoreConfig('paypal/account/charge_value');
		}

        // regular totals
        $shippingDescription = '';
        if ($this->_salesEntity instanceof Mage_Sales_Model_Order) {
            $shippingDescription = $this->_salesEntity->getShippingDescription();
            $this->_totals = array(
                self::TOTAL_SUBTOTAL => $this->_salesEntity->getBaseSubtotal(),
                self::TOTAL_TAX      => $this->_salesEntity->getBaseTaxAmount(),
                self::TOTAL_SHIPPING => $this->_salesEntity->getBaseShippingAmount(),
                self::TOTAL_DISCOUNT => abs($this->_salesEntity->getBaseDiscountAmount()),
            );
			if ($chargeType=="percentage") {
				if(Mage::getStoreConfig('tax/calculation/price_includes_tax')!=1)
        			$this->_totals[self::TOTAL_SUBTOTAL] += ($this->_totals[self::TOTAL_SUBTOTAL]+$this->_totals[self::TOTAL_TAX]) * floatval($chargeValue) / 100;
				else
        			$this->_totals[self::TOTAL_SUBTOTAL] += ($this->_totals[self::TOTAL_SUBTOTAL]) * floatval($chargeValue) / 100;
        	}
        	else {
        		$this->_totals[self::TOTAL_SUBTOTAL] += floatval($chargeValue);        			       			      			
        	}
            $this->_applyHiddenTaxWorkaround($this->_salesEntity);
        } else {
            $address = $this->_salesEntity->getIsVirtual() ?
                $this->_salesEntity->getBillingAddress() : $this->_salesEntity->getShippingAddress();
            $shippingDescription = $address->getShippingDescription();
            $this->_totals = array (
                self::TOTAL_SUBTOTAL => $this->_salesEntity->getBaseSubtotal(),
                self::TOTAL_TAX      => $address->getBaseTaxAmount(),
                self::TOTAL_SHIPPING => $address->getBaseShippingAmount(),
                self::TOTAL_DISCOUNT => abs($address->getBaseDiscountAmount()),
            );
			if ($chargeType=="percentage") {
				if(Mage::getStoreConfig('tax/calculation/price_includes_tax')!=1)
	        		$this->_totals[self::TOTAL_SUBTOTAL] += ($this->_totals[self::TOTAL_SUBTOTAL]+$this->_totals[self::TOTAL_TAX]) * floatval($chargeValue) / 100;
				else
        			$this->_totals[self::TOTAL_SUBTOTAL] += ($this->_totals[self::TOTAL_SUBTOTAL]) * floatval($chargeValue) / 100;
        	}
        	else {
        		$this->_totals[self::TOTAL_SUBTOTAL] += floatval($chargeValue);        			       			      			
        	}
            $this->_applyHiddenTaxWorkaround($address);
        }
        $originalDiscount = $this->_totals[self::TOTAL_DISCOUNT];

        // arbitrary items, total modifications
        Mage::dispatchEvent('paypal_prepare_line_items', array('paypal_cart' => $this));

        // distinguish original discount among the others
        if ($originalDiscount > 0.0001 && isset($this->_totalLineItemDescriptions[self::TOTAL_DISCOUNT])) {
            $this->_totalLineItemDescriptions[self::TOTAL_DISCOUNT][] = Mage::helper('sales')->__('Discount (%s)', Mage::app()->getStore()->convertPrice($originalDiscount, true, false));
        }

        // discount, shipping as items
        if ($this->_isDiscountAsItem && $this->_totals[self::TOTAL_DISCOUNT]) {
            $this->addItem(Mage::helper('paypal')->__('Discount'), 1, -1.00 * $this->_totals[self::TOTAL_DISCOUNT],
                $this->_renderTotalLineItemDescriptions(self::TOTAL_DISCOUNT)
            );
        }
        $shippingItemId = $this->_renderTotalLineItemDescriptions(self::TOTAL_SHIPPING, $shippingDescription);
        if ($this->_isDiscountAsItem && $this->_isShippingAsItem && (float)$this->_totals[self::TOTAL_SHIPPING]) {
            $this->addItem(Mage::helper('paypal')->__('Shipping'), 1, (float)$this->_totals[self::TOTAL_SHIPPING],
                $shippingItemId
            );
        }

        // compound non-regular items into subtotal
        foreach ($this->_items as $key => $item) {
            if ($key > $lastRegularItemKey && $item->getAmount() != 0) {
                $this->_totals[self::TOTAL_SUBTOTAL] += $item->getAmount();
            }
        }

        $this->_validate();
        // if cart items are invalid, prepare cart for transfer without line items
        if (!$this->_areItemsValid) {
            $this->removeItem($shippingItemId);
        }

        $this->_shouldRender = false;
		
		return parent::_render();
    }
	
	private function _applyHiddenTaxWorkaround($salesEntity)
    {
        $this->_totals[self::TOTAL_TAX] += (float)$salesEntity->getBaseHiddenTaxAmount();
        $this->_totals[self::TOTAL_TAX] += (float)$salesEntity->getBaseShippingHiddenTaxAmount();
    }

}