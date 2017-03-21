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
        if ($store == null) {
            $store = Mage::app()->getStore();
        }

        $this->helperData->loginSDK($store);

        $transaction = \Paynl\Transaction::get($transactionId);
        $transactionInfo = $transaction->getData();
        $status = Pay_Payment_Model_Transaction::STATE_PENDING;

        //status bepalen
        if ($transaction->isPaid()) {
            $status = Pay_Payment_Model_Transaction::STATE_SUCCESS;
        } elseif ($transactionInfo['paymentDetails']['state'] == 95) {
            $status = Pay_Payment_Model_Transaction::STATE_AUTHORIZED;
        } elseif ($transaction->isCancelled()) {
            $status = Pay_Payment_Model_Transaction::STATE_CANCELED;
        } else {
            $status = Pay_Payment_Model_Transaction::STATE_PENDING;
            //we gaan geen update doen
            return;
        }
        $paidAmount = $transaction->getPaidAmount();
        $endUserId = $transaction->getAccountNumber();

        // alle data is opgehaald status updaten
        $this->updateState($transactionId, $status, $paidAmount, $endUserId, $store);

        return $status;
    }

    public function updateState($transactionId, $status, $paidAmount = null, $endUserId = null, $store = null)
    {
        //transactie ophalen uit pay tabel
        /** @var Pay_Payment_Helper_Data $helperData */
        $helperData = Mage::helper('pay_payment');
        $transaction = $helperData->getTransaction($transactionId);

        $order = $this->getOrderByTransactionId($transactionId);
        $payment = $order->getPayment();

        if ($store == null) {
            $store = $order->getStore();
        }

        if ($transaction->getStatus() == $status || $order->getTotalDue() == 0) {
            //status is al verwerkt - geen actie vereist
            throw Mage::exception('Pay_Payment', 'Already processed', 0);
        }
        $autoInvoice = $store->getConfig('pay_payment/general/auto_invoice');
        $invoiceEmail = $store->getConfig('pay_payment/general/invoice_email');

        if ($status == Pay_Payment_Model_Transaction::STATE_SUCCESS) {
            // als het order al canceled was, gaan we hem nu uncancelen
            if ($order->isCanceled()) {
                $this->uncancel($order);
            }

            $orderAmount = $order->getGrandTotal();

            //controleren of het gehele bedrag betaald is
            if (abs($orderAmount-$paidAmount) < 0.0001) {
                $order->addStatusHistoryComment('Bedrag komt niet overeen. Order bedrag: ' . $orderAmount . ' Betaald: ' . $paidAmount);
            }


            if ($autoInvoice) {
                $payment->setTransactionId($transactionId);
                $payment->setCurrencyCode($order->getOrderCurrencyCode());
                $payment->setShouldCloseParentTransaction(true);
                $payment->setIsTransactionClosed(0);
                $payment->registerCaptureNotification(
                    $order->getGrandTotal(), true
                );

                $invoice = $this->_getInvoiceForTransactionId($order, $transactionId);

                if (is_bool($invoice) && $invoice == false) // er is nog geen invoice gemaakt
                {
                    if ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) // Om een of andere reden kan een state in payment_review komen, dit is slecht, want dit veroorzaakt een lock
                    {
                        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
                        $order->save();
                    }

                    if (!$order->canInvoice()) {
                        die('Cannot create an invoice.');
                    }

                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

                    if (!$invoice->getTotalQty()) {
                        die('Cannot create an invoice without products.');
                    }

                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());

                    $transactionSave->save();
                }


                // fix voor tax in invoice
                $invoice->setTaxAmount($order->getTaxAmount());
                $invoice->setBaseTaxAmount($order->getBaseTaxAmount());
                $invoice->save();
                $order->setTaxInvoiced($invoice->getTaxAmount());
                $order->save();

                if ($invoiceEmail) {
                    $invoice->sendEmail();
                    $invoice->setEmailSent(true);
                    $invoice->save();
                }
            }


            // ingestelde status ophalen
            $processedStatus = $store->getConfig('payment/' . $payment->getMethod() . '/order_status_success');

            $order->setIsInProcess(true);

            $stateMessage = 'Betaling ontvangen, klantkenmerk: ' . $endUserId;

            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING, $processedStatus, $stateMessage, true
            );
            $order->save();

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


            return true;
        } elseif ($status == Pay_Payment_Model_Transaction::STATE_AUTHORIZED) {
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
                throw Mage::exception('Pay_Payment', 'Cannot cancel already paid order');
            }

            // order annuleren
            // if the order has an authorization, close it so the api doesn't get called
            if($order->getPayment()->getAuthorizationTransaction()){
                $order->getPayment()->getAuthorizationTransaction()->close(true);
            }
            $order->cancel();
            $order->save();
            $sendStatusupdates = $store->getConfig('pay_payment/general/send_statusupdates');
            if ($sendStatusupdates) {
                $order->sendOrderUpdateEmail();
            }
            // transactie in pay tabel updaten
            $transaction->setStatus($status);
            $transaction->setLastUpdate(time());
            $transaction->save();

            return true;
        } else {
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
