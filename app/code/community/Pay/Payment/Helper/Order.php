<?php

class Pay_Payment_Helper_Order extends Mage_Core_Helper_Abstract
{

    /**
     * @var Pay_Payment_Helper_Data
     */
    private $helperData;

    public function __construct()
    {
        $this->helperData = Mage::helper('pay_payment');
    }

    /**
     * Processes the order by transactionId, the data is collected by calling the pay api
     *
     * @param string $transactionId
     * @param Mage_Core_Model_Store $store
     * @return string|null the new status
     */
    public function processByTransactionId($transactionId, $store = null)
    {
        $order = $this->getOrderByTransactionId($transactionId);
        if ($store == null) {
            $store = $order->getStore();
        }
        $extended_logging = Mage::getStoreConfig('pay_payment/general/extended_logging', $store);

        if($extended_logging) $order->addStatusHistoryComment('Exchange call received from pay.nl');

        $this->helperData->loginSDK($store);

        $transaction = \Paynl\Transaction::get($transactionId);
        $methodName = $transaction->getPaymentMethodName();
        $transactionInfo = $transaction->getData();
        $status = Pay_Payment_Model_Transaction::STATE_PENDING;

        //status bepalen
        if ($transaction->isPaid()) {
            $status = Pay_Payment_Model_Transaction::STATE_SUCCESS;
        } elseif ($transactionInfo['paymentDetails']['state'] == 95) {
            $status = Pay_Payment_Model_Transaction::STATE_AUTHORIZED;
        } elseif ($transaction->isCancelled()) {
            $status = Pay_Payment_Model_Transaction::STATE_CANCELED;
        } elseif($transaction->isBeingVerified()){
	        $status = Pay_Payment_Model_Transaction::STATE_VERIFY;
        }else {
            $status = Pay_Payment_Model_Transaction::STATE_PENDING;
            if($extended_logging) $order->addStatusHistoryComment('Status is pending, exiting');
            $order->save();
            //we gaan geen update doen
            return;
        }

        $paidAmount = $transaction->getPaidAmount();
        $endUserId = $transaction->getAccountNumber();

        if($extended_logging) $order->addStatusHistoryComment('Processing exchange with status '. $status);
        $order->save();

        // alle data is opgehaald status updaten
        $this->updateState($transactionId, $status, $methodName, $paidAmount, $endUserId, $store);

        return $status;
    }

    public function updateState($transactionId, $status, $methodName, $paidAmount = null, $endUserId = null, $store = null)
    {
        //transactie ophalen uit pay tabel
        /** @var Pay_Payment_Helper_Data $helperData */
        $helperData = Mage::helper('pay_payment');
        $transaction = $helperData->getTransaction($transactionId);

        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getOrderByTransactionId($transactionId);

        if ($store == null) {
            $store = $order->getStore();
        }

        $extended_logging = Mage::getStoreConfig('pay_payment/general/extended_logging', $store);
        $payment = $order->getPayment();

	    $hash = $payment->getAdditionalInformation( 'paynl_instore_hash');

        if (($transaction->getStatus() == $status || $order->getTotalDue() == 0) )
        {
            //status is al verwerkt - geen actie vereist
            if($extended_logging) $order->addStatusHistoryComment('Stopped processing the order, already processed');
            $order->save();
            throw Mage::exception('Pay_Payment', 'Already processed', 0);
        }

        $autoInvoice = $store->getConfig('pay_payment/general/auto_invoice');
        $invoiceEmail = $store->getConfig('pay_payment/general/invoice_email');

        if($invoiceEmail){
            $invoiceEmail = $store->getConfig('payment/' . $payment->getMethod() . '/invoice_email');
        }

        if ($status == Pay_Payment_Model_Transaction::STATE_SUCCESS) {
	        if(!empty($hash)){
		        $receiptData = \Paynl\Instore::getReceipt( array( 'hash' => $hash ) );
		        $approvalId  = $receiptData->getApprovalId();
		        $receipt     = $receiptData->getReceipt();
		        $payment->setAdditionalInformation( 'paynl_receipt', $receipt );
		        $payment->setAdditionalInformation( 'paynl_transaction_id', $approvalId );
	        }
            // als het order al canceled was, gaan we hem nu uncancelen
            if ($order->isCanceled()) {
                if($extended_logging) $order->addStatusHistoryComment('Un-cancelling order');
                $this->uncancel($order);
            }

            $orderAmount = $order->getGrandTotal()*1;
            $paidAmount = $paidAmount*1;

	        if( $payment->getMethod() == 'multipaymentforpos' ) {
		        $order->setTotalPaid( $order->getTotalPaid() + $paidAmount );
	        }

            //controleren of het gehele bedrag betaald is
            if (abs($orderAmount-$paidAmount) >= 0.01) {
                $order->addStatusHistoryComment('Bedrag komt niet overeen. Order bedrag: ' . $orderAmount . ' Betaald: ' . $paidAmount);

                if($payment->getMethod() == 'pay_payment_instore'){
                    $order->setTotalPaid( $order->getTotalPaid() + $paidAmount );
                }
            }


            if ($autoInvoice) {
                if($extended_logging) $order->addStatusHistoryComment('Starting invoicing');
                $payment->setTransactionId($transactionId);
                $payment->setCurrencyCode($order->getOrderCurrencyCode());
                $payment->setShouldCloseParentTransaction(true);
                $payment->setIsTransactionClosed(0);


                $payment->registerCaptureNotification(
                    $paidAmount, true
                );

                $invoice = $this->_getInvoiceForTransactionId($order, $transactionId);

                if (is_bool($invoice) && $invoice == false && $order->getTotalDue() == 0) // er is nog geen invoice gemaakt en er staat niets open
                {
                    if ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) // Om een of andere reden kan een state in payment_review komen, dit is slecht, want dit veroorzaakt een lock
                    {
                        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
                    }

                    if (!$order->canInvoice()) {
                        if($extended_logging) $order->addStatusHistoryComment('Pay.nl cannot create invoice');
                        $order->save();
                        die('Cannot create an invoice.');
                    }

	                /**
	                 * @var Mage_Sales_Model_Order_Invoice $invoice
	                 */
                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

                    if (!$invoice->getTotalQty()) {
                        if($extended_logging) $order->addStatusHistoryComment('Pay.nl Cannot create an invoice without products.');
                        $order->save();
                        die('Cannot create an invoice without products.');
                    }

                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());

                    $transactionSave->save();
                }

                if( $payment->getMethod() == 'multipaymentforpos'){
                    /* reset total_paid & base_total_paid in order */
                    $order->setTotalPaid($order->getTotalPaid() - $invoice->getGrandTotal());
                    $order->setBaseTotalPaid($order->getBaseTotalPaid() - $invoice->getBaseGrandTotal());
                }
                if($invoice) {
                    // fix voor tax in invoice
                    $invoice->setTaxAmount($order->getTaxAmount());
                    $invoice->setBaseTaxAmount($order->getBaseTaxAmount());
                    $order->setTaxInvoiced($invoice->getTaxAmount());
                    if ($invoiceEmail) {
                        $invoice->sendEmail();
                        $invoice->setEmailSent(true);
                    }
                    $invoice->save();
                }

                $payment->save();
                $order->save();
            }

            // ingestelde status ophalen
            $processedStatus = $store->getConfig('payment/' . $payment->getMethod() . '/order_status_success');

            $order->setIsInProcess(true);

            $stateMessage = 'Betaling ontvangen via '.$methodName.', klantkenmerk: ' . $endUserId;

            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING, $processedStatus, $stateMessage, true
            );

            $sendMail = $store->getConfig('payment/' . $payment->getMethod() . '/send_mail');
            $sendStatusupdates = $store->getConfig('pay_payment/general/send_statusupdates');

            if ($sendMail == 'start') {
                // De bevestigingsmail is al verstuurd, we gaan alleen de update sturen
                if ($sendStatusupdates) {
                    $order->sendOrderUpdateEmail();
                }
            } else {
                // De bevestigingsmail is nog niet verstuurd, dus doen we het nu
                if (!$order->getEmailSent()) {
                    $order->sendNewOrderEmail();
                    $order->setEmailSent(true);
                }
            }

            $order->save();

            //transactie in pay tabel updaten
            $transaction->setStatus($status);
            $transaction->setLastUpdate(time());
            $transaction->save();

            $eventData = array(
                'order' => $order,
                'payment' => $payment,
                'transaction' => array(
                    'id' => $transactionId,
                    'status' => 'paid'
                )
            );
            if($receiptData){
                $eventData['transaction']['receipt'] = $receiptData->getReceipt();
            }
            Mage::dispatchEvent('paynl_transaction_complete', $eventData);

            return true;
        } elseif ($status == Pay_Payment_Model_Transaction::STATE_AUTHORIZED) {
            if($extended_logging) $order->addStatusHistoryComment('Registering authorization');

            $payment = $order->getPayment();
            $payment->registerAuthorizationNotification($order->getTotalDue());
            $payment->setTransactionId($transactionId);

            $auth_transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
//            $mage_transaction->setTxnId($transactionId);
            $auth_transaction->setIsClosed(0);
            $auth_transaction->save();
            $payment->save();

            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'processing', '', false);

            $order->save();

            $payment->save();

        } elseif ($status == Pay_Payment_Model_Transaction::STATE_CANCELED) {
            /** @var $order Mage_Sales_Model_Order */
            if ($order->getTotalDue() <= 0 ||
                $transaction->getStatus() == Pay_Payment_Model_Transaction::STATE_SUCCESS ||
                $helperData->isOrderPaid($order->getId())
            ) {
                if($extended_logging) $order->addStatusHistoryComment('Cannot cancel already paid order');

                throw Mage::exception('Pay_Payment', 'Cannot cancel already paid order');
            }

            // order annuleren
            // if the order has an authorization, close it so the api doesn't get called
            if($order->getPayment()->getAuthorizationTransaction()){
                if($extended_logging) $order->addStatusHistoryComment('Closing authorization');
                $order->getPayment()->getAuthorizationTransaction()->close(true);
            }
            $order->cancel();
            if($extended_logging) $order->addStatusHistoryComment('Order canceled');
            $order->save();
            $sendStatusupdates = $store->getConfig('pay_payment/general/send_statusupdates');
            if ($sendStatusupdates) {
                $order->sendOrderUpdateEmail();
            }
            // transactie in pay tabel updaten
            $transaction->setStatus($status);
            $transaction->setLastUpdate(time());
            $transaction->save();

            $eventData = array(
                'order' => $order,
                'payment' => $payment,
                'transaction' => array(
                    'id' => $transactionId,
                    'status' => 'canceled'
                )
            );

            Mage::dispatchEvent('paynl_transaction_complete', $eventData);


            return true;
        } elseif ($status == Pay_Payment_Model_Transaction::STATE_VERIFY){
        	$method = $payment->getMethod();
        	$configPath = "payment/{$method}/order_status_verify";
        	$verify_status = Mage::getStoreConfig($configPath, $order->getStore());

			$order->addStatusHistoryComment('Transaction needs to be verified', $verify_status);
			$order->save();

	        $transaction->setStatus($status);
	        $transaction->setLastUpdate(time());
	        $transaction->save();
			return true;

        }else {
            throw Mage::exception('Pay_Payment', 'Unknown status ' . $status, 1);
        }
    }

    /**
     * Returns the order object
     *
     * @param int $transactionId
     * @return Mage_Sales_Model_Order
     */
    public function getOrderByTransactionId($transactionId)
    {
        //transactie ophalen uit pay tabel
        $transaction = $this->helperData->getTransaction($transactionId);

        //order ophalen
        $order = Mage::getModel('sales/order');
        /* @var $order Mage_Sales_Model_Order */
        $order->load($transaction->getOrderId());

        return $order;
    }

    public function uncancel($order)
    {
        if ($order->getId()) {
            $order->setData('state', 'processing')
                ->setData('status', 'processing')
                ->setData('base_discount_canceled', 0)
                ->setData('base_shipping_canceled', 0)
                ->setData('base_subtotal_canceled', 0)
                ->setData('base_tax_canceled', 0)
                ->setData('base_total_canceled', 0)
                ->setData('discount_canceled', 0)
                ->setData('shipping_canceled', 0)
                ->setData('subtotal_canceled', 0)
                ->setData('tax_canceled', 0)
                ->setData('total_canceled', 0);

            $items = $order->getItemsCollection();
            $productUpdates = array();
            foreach ($items as $item) {
                $canceled = $item->getQtyCanceled();
                if ($canceled > 0) {
                    $productUpdates[$item->getProductId()] = array('qty' => $canceled);
                }
                $item->setData('qty_canceled', 0);
            }
            try {
                Mage::getSingleton('cataloginventory/stock')->registerProductsSale($productUpdates);
                $items->save();
                $currentState = $order->getState();
                $currentStatus = $order->getStatus();

                $order->setState(
                    $currentState, $currentStatus, Mage::helper('adminhtml')->__('Order uncanceled'), false
                )->save();
                $order->save();
            } catch (Exception $ex) {
                Mage::log('Error uncancel order: ' . $ex->getMessage());
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Return invoice model for transaction
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $transactionId
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function _getInvoiceForTransactionId($order, $transactionId)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->getTransactionId() == $transactionId) {
                return $invoice;
            }
        }
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_OPEN) {
                $invoice->setTransactionId($transactionId);
                return $invoice;
            }
        }

        return false;
    }

    public function getTransactionStatus($transactionId, $store = null)
    {
        $this->helperData->loginSDK($store);

        $transaction = \Paynl\Transaction::get($transactionId);

        $status = Pay_Payment_Model_Transaction::STATE_PENDING;

        //status bepalen
        if ($transaction->isPaid()) {
            return Pay_Payment_Model_Transaction::STATE_SUCCESS;
        } elseif ($transaction->isCanceled()) {
            return Pay_Payment_Model_Transaction::STATE_CANCELED;
        } else {
            return Pay_Payment_Model_Transaction::STATE_PENDING;
        }
    }

}
