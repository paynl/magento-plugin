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
     *
     * @return string|null the new status
     */
    public function processByTransactionId($transactionId, $store = null)
    {
        $order = $this->getOrderByTransactionId($transactionId);
        if ($store == null) {
            $store = $order->getStore();
        }
        $extended_logging = Mage::getStoreConfig('pay_payment/general/extended_logging', $store);
        $blockCapture     = $store->getConfig('pay_payment/general/block_capture') == 1;
        if ($extended_logging) {
            $order->addStatusHistoryComment('Exchange call received from pay.nl');
        }
        $this->helperData->loginSDK($store);
        $transaction     = \Paynl\Transaction::get($transactionId);
        $methodName      = $transaction->getPaymentMethodName();
        $transactionInfo = $transaction->getData();
        $status          = Pay_Payment_Model_Transaction::STATE_PENDING;
        //status bepalen
        if ($transaction->isPaid()) {
            $status = Pay_Payment_Model_Transaction::STATE_SUCCESS;
        } elseif ($transactionInfo['paymentDetails']['state'] == 95) {
            $status = Pay_Payment_Model_Transaction::STATE_AUTHORIZED;
        } elseif ($transaction->isCancelled()) {
            $status = Pay_Payment_Model_Transaction::STATE_CANCELED;
        } elseif ($transaction->isBeingVerified()) {
            $status = Pay_Payment_Model_Transaction::STATE_VERIFY;
        } else {
            $status = Pay_Payment_Model_Transaction::STATE_PENDING;
            if ($extended_logging) {
                $order->addStatusHistoryComment('Status is pending, exiting');
            }
            $order->save();
            //we gaan geen update doen
            return;
        }
        $authTransaction = $order->getPayment()->getAuthorizationTransaction();
        if ($status == Pay_Payment_Model_Transaction::STATE_SUCCESS &&
            $authTransaction &&
            $blockCapture
        ) {
            if ($extended_logging) {
                $order->addStatusHistoryComment('Blocking capture because block capture is enabled and an authorize transaction is present.');
            }
            $order->save();
            return $status;
        }
        $paidAmount = $transaction->getPaidCurrencyAmount();
        if ($store->getConfig('pay_payment/general/only_base_currency') == 1) {
            $paidAmount = $transaction->getPaidAmount();
        }
        $endUserId = $transaction->getAccountNumber();
        if ($extended_logging) {
            $order->addStatusHistoryComment('Processing exchange with status ' . $status);
        }
        $order->save();
        // alle data is opgehaald status updaten
        $this->updateState($transactionId, $status, $methodName, $paidAmount, $endUserId, $store);
        return $status;
    }
    /**
     * Returns the order object
     *
     * @param int $transactionId
     *
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
    public function updateState(
        $transactionId,
        $status,
        $methodName,
        $paidAmount = null,
        $endUserId = null,
        $store = null
    ) {
        //transactie ophalen uit pay tabel
        /** @var Pay_Payment_Helper_Data $helperData */
        $helperData  = Mage::helper('pay_payment');
        $transaction = $helperData->getTransaction($transactionId);
        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getOrderByTransactionId($transactionId);
        if ($store == null) {
            $store = $order->getStore();
        }
        $extended_logging = Mage::getStoreConfig('pay_payment/general/extended_logging', $store);
        $payment          = $order->getPayment();
        $hash = $payment->getAdditionalInformation('paynl_instore_hash');
        if (($transaction->getStatus() == $status || $order->getTotalDue() == 0)) {
            //status is al verwerkt - geen actie vereist
            if ($extended_logging) {
                $order->addStatusHistoryComment('Stopped processing the order, already processed');
            }
            $order->save();
            throw Mage::exception('Pay_Payment', 'Already processed', 0);
        }
        $autoInvoice      = $store->getConfig('pay_payment/general/auto_invoice');
        $neverCancel      = $store->getConfig('pay_payment/general/never_cancel');
        $invoiceEmail     = $store->getConfig('pay_payment/general/invoice_email');
        $onlyBaseCurrency = $store->getConfig('pay_payment/general/only_base_currency') == 1;
        if ($invoiceEmail) {
            $invoiceEmail = $store->getConfig('payment/' . $payment->getMethod() . '/invoice_email');
        }
        if ($status == Pay_Payment_Model_Transaction::STATE_SUCCESS) {
            if ( ! empty($hash)) {
                $receiptData = \Paynl\Instore::getReceipt(array('hash' => $hash));
                $approvalId  = $receiptData->getApprovalId();
                $receipt     = $receiptData->getReceipt();
                $payment->setAdditionalInformation('paynl_receipt', $receipt);
                $payment->setAdditionalInformation('paynl_transaction_id', $approvalId);
            }
            // als het order al canceled was, gaan we hem nu uncancelen
            if ($order->isCanceled()) {
                if ($extended_logging) {
                    $order->addStatusHistoryComment('Un-cancelling order');
                }
                $this->uncancel($order);
            }
            $orderAmount = round($order->getTotalDue() * 1, 2);
            if ($onlyBaseCurrency) {
                $orderAmount = $order->getBaseTotalDue() * 1;
            }
            $paidAmount = round($paidAmount * 1, 2);
            //controleren of het gehele bedrag betaald is
            if (abs($orderAmount - $paidAmount) >= 0.0001) {
                $order->addStatusHistoryComment('Bedrag komt niet overeen. Order bedrag: ' . $orderAmount . ' Betaald: ' . $paidAmount);
                $order->setTotalPaid($order->getTotalPaid() + $paidAmount);
                $order->setBaseTotalPaid($order->getBaseTotalPaid() + $paidAmount);
            }
            $order->save();
            if ($autoInvoice) {
                if ($extended_logging) {
                    $order->addStatusHistoryComment('Starting invoicing');
                }
                $payment->setTransactionId($transactionId);
                $payment->setCurrencyCode($order->getBaseCurrencyCode());
                $captureAmount = $paidAmount;
                // Always register the payment in base currency because other currencies are always suspected fraud
                if ($order->getOrderCurrency() != $order->getBaseCurrency()) {
                    $captureAmount = $order->getBaseGrandTotal();
                }
                $payment->setShouldCloseParentTransaction(true);
                $payment->setIsTransactionClosed(0);
                $payment->registerCaptureNotification(
                    $captureAmount, true
                );
                if ($payment->getMethod() == 'multipaymentforpos') {
                    if ($order->getTotalDue() != 0) {
                        if ($paidAmount == $orderAmount) {
                            $order->setTotalPaid($order->getGrandTotal());
                            $order->setBaseTotalPaid($order->getBaseGrandTotal());
                            $order->save();
                        }
                    }
                }
                $invoice = $this->_getInvoiceForTransactionId($order, $transactionId);
                if (is_bool($invoice) && $invoice == false &&
                    round($order->getTotalDue(), 2) == 0) // er is nog geen invoice gemaakt en er staat niets open
                {
                    if ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) // Om een of andere reden kan een state in payment_review komen, dit is slecht, want dit veroorzaakt een lock
                    {
                        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
                    }
                    if ( ! $order->canInvoice()) {
                        if ($extended_logging) {
                            $order->addStatusHistoryComment('Pay.nl cannot create invoice');
                        }
                        $order->save();
                        die('Cannot create an invoice.');
                    }
                    /**
                     * @var Mage_Sales_Model_Order_Invoice $invoice
                     */
                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                    if ( ! $invoice->getTotalQty()) {
                        if ($extended_logging) {
                            $order->addStatusHistoryComment('Pay.nl Cannot create an invoice without products.');
                        }
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
                if ($invoice) {
                    // fix voor tax in invoice
                    $invoice->setTaxAmount($order->getTaxAmount());
                    $invoice->setBaseTaxAmount($order->getBaseTaxAmount());
                    $order->setTaxInvoiced($invoice->getTaxAmount());
                    $invoice->save();
                    if ($invoiceEmail) {
                        $invoice->sendEmail();
                        $invoice->setEmailSent(true);
                        $invoice->save();
                    }
                }
                $payment->save();
                $order->save();
            }
            // ingestelde status ophalen
            $processedStatus = $store->getConfig('payment/' . $payment->getMethod() . '/order_status_success');
            $order->setIsInProcess(true);
            $stateMessage = 'Betaling ontvangen via ' . $methodName . ', klantkenmerk: ' . $endUserId;
            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING, $processedStatus, $stateMessage, true
            );
            $sendMail          = $store->getConfig('payment/' . $payment->getMethod() . '/send_mail');
            $sendStatusupdates = $store->getConfig('pay_payment/general/send_statusupdates');
            if ($sendMail == 'start') {
                // De bevestigingsmail is al verstuurd, we gaan alleen de update sturen
                if ($sendStatusupdates) {
                    $order->sendOrderUpdateEmail();
                }
            } else {
                // De bevestigingsmail is nog niet verstuurd, dus doen we het nu
                if ( ! $order->getEmailSent()) {
                    $order->sendNewOrderEmail();
                    $order->setEmailSent(true);
                }
            }
            /**
             * Sometimes totalpaid is not updated, but the payment and invoice are added.
             * If this is the case, update totalpaid to the order amount
             */
            $totalDue  = round($order->getTotalDue(), 2);
            $totalPaid = round($order->getTotalPaid(), 2);
            if($extended_logging){
                $order->addStatusHistoryComment("TotalDue: $totalDue, TotalPaid: $totalPaid");
            }
            if ($totalDue != 0 &&
                $totalDue == $paidAmount && // only register the payment if totaldue equals the whole amount
                $totalPaid == 0 // skip this if this is a partial payment
            ) {
                $order->setTotalPaid($order->getGrandTotal());
                $order->setBaseTotalPaid($order->getBaseGrandTotal());
                $order->addStatusHistoryComment('Pay.nl - Updated totalPaid, because it seems like the payment was not correctly registered');
            }
            # If multi payment, reset the paid amount
            if ($payment->getMethod() == 'multipaymentforpos' && $paidAmount == $orderAmount) {
                $order->setBaseTotalPaid($order->getBaseGrandTotal());
                $order->setTotalPaid($order->getGrandTotal());
            }
            $order->save();
            //transactie in pay tabel updaten
            $transaction->setStatus($status);
            $transaction->setLastUpdate(time());
            $transaction->save();
            $eventData = array(
                'order'       => $order,
                'payment'     => $payment,
                'transaction' => array(
                    'id'     => $transactionId,
                    'status' => 'paid'
                )
            );
            if ($receiptData) {
                $eventData['transaction']['receipt'] = $receiptData->getReceipt();
            }
            Mage::dispatchEvent('paynl_transaction_complete', $eventData);
            return true;
        } elseif ($status == Pay_Payment_Model_Transaction::STATE_AUTHORIZED) {
            if ($extended_logging) {
                $order->addStatusHistoryComment('Registering authorization');
            }
            $payment = $order->getPayment();
            $payment->registerAuthorizationNotification($order->getTotalDue());
            $payment->setTransactionId($transactionId);
            $auth_transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
//            $mage_transaction->setTxnId($transactionId);
            $auth_transaction->setIsClosed(0);
            $auth_transaction->save();
            $payment->save();
            $authorizedStatus = $store->getConfig('payment/' . $payment->getMethod() . '/order_status_authorized');
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $authorizedStatus, '', false);
            $order->save();
            $payment->save();
        } elseif ($status == Pay_Payment_Model_Transaction::STATE_CANCELED) {
            /** @var $order Mage_Sales_Model_Order */
            if ($order->getTotalDue() <= 0 ||
                $transaction->getStatus() == Pay_Payment_Model_Transaction::STATE_SUCCESS ||
                $helperData->isOrderPaid($order->getId())
            ) {
                if ($extended_logging) {
                    $order->addStatusHistoryComment('Cannot cancel already paid order');
                }
                throw Mage::exception('Pay_Payment', 'Cannot cancel already paid order');
            }
            // order annuleren
            // if the order has an authorization, close it so the api doesn't get called
            if ($order->getPayment()->getAuthorizationTransaction()) {
                if ($extended_logging) {
                    $order->addStatusHistoryComment('Closing authorization');
                }
                $order->getPayment()->getAuthorizationTransaction()->close(true);
            }
            if ($neverCancel) {
                if ($extended_logging) {
                    $order->addStatusHistoryComment('Order not canceled, never cancel order is turned on');
                    $order->save();
                }
            } else {
                $order->cancel();
                if ($extended_logging) {
                    $order->addStatusHistoryComment('Order canceled');
                }
                $order->save();
                $sendStatusupdates = $store->getConfig('pay_payment/general/send_statusupdates');
                if ($sendStatusupdates) {
                    $order->sendOrderUpdateEmail();
                }
            }
            // transactie in pay tabel updaten
            $transaction->setStatus($status);
            $transaction->setLastUpdate(time());
            $transaction->save();
            $eventData = array(
                'order'       => $order,
                'payment'     => $payment,
                'transaction' => array(
                    'id'     => $transactionId,
                    'status' => 'canceled'
                )
            );
            Mage::dispatchEvent('paynl_transaction_complete', $eventData);
            return true;
        } elseif ($status == Pay_Payment_Model_Transaction::STATE_VERIFY) {
            $method        = $payment->getMethod();
            $configPath    = "payment/{$method}/order_status_verify";
            $verify_status = Mage::getStoreConfig($configPath, $order->getStore());
            $order->addStatusHistoryComment('Transaction needs to be verified', $verify_status);
            $order->save();
            $transaction->setStatus($status);
            $transaction->setLastUpdate(time());
            $transaction->save();
            return true;
        } else {
            throw Mage::exception('Pay_Payment', 'Unknown status ' . $status, 1);
        }
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
            $items          = $order->getItemsCollection();
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
                $currentState  = $order->getState();
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
     *
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