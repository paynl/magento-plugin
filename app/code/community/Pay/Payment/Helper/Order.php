<?php

class Pay_Payment_Helper_Order extends Mage_Core_Helper_Abstract
{

    public function uncancel($order)
    {
        if ($order->getId())
        {
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
            foreach ($items as $item)
            {
                $canceled = $item->getQtyCanceled();
                if ($canceled > 0)
                {
                    $productUpdates[$item->getProductId()] = array('qty' => $canceled);
                }
                $item->setData('qty_canceled', 0);
            }
            try
            {
                Mage::getSingleton('cataloginventory/stock')->registerProductsSale($productUpdates);
                $items->save();
                $currentState = $order->getState();
                $currentStatus = $order->getStatus();

                $order->setState(
                        $currentState, $currentStatus, Mage::helper('adminhtml')->__('Order uncanceled'), false
                )->save();
                $order->save();
            } catch (Exception $ex)
            {
                Mage::log('Error uncancel order: ' . $ex->getMessage());
                return false;
            }
            return true;
        }
        return false;
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
        $helperData = Mage::helper('pay_payment');
        $transaction = $helperData->getTransaction($transactionId);

        //order ophalen
        $order = Mage::getModel('sales/order');
        /* @var $order Mage_Sales_Model_Order */
        $order->load($transaction->getOrderId());

        return $order;
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
        foreach ($order->getInvoiceCollection() as $invoice)
        {
            if ($invoice->getTransactionId() == $transactionId)
            {
                return $invoice;
            }
        }
        foreach ($order->getInvoiceCollection() as $invoice)
        {
            if ($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_OPEN)
            {
                $invoice->setTransactionId($transactionId);
                return $invoice;
            }
        }

        return false;
    }

    public function updateState($transactionId, $status, $paidAmount = null, $endUserId = null, $store = null)
    {
        if ($store == null)
        {
            $store = Mage::app()->getStore();
        }

        //$transactionInfo = $this->getTransactionInfo($transactionId, $store);
        //transactie ophalen uit pay tabel
        $helperData = Mage::helper('pay_payment');
        $transaction = $helperData->getTransaction($transactionId);

        $order = $this->getOrderByTransactionId($transactionId);



        if ($transaction->getStatus() == $status)
        {
            //status is al verwerkt - geen actie vereist
            throw Mage::exception('Pay_Payment', 'Already processed', 0);
        }

        if ($status == Pay_Payment_Model_Transaction::STATE_SUCCESS)
        {
            // als het order al canceled was, gaan we hem nu uncancelen
            if ($order->isCanceled())
            {
                $this->uncancel($order);
            }

            $orderAmount = intval(round($order->getGrandTotal() * 100));
            $paidAmount = intval(round($paidAmount));

            //controleren of het gehele bedrag betaald is
            if ($orderAmount != $paidAmount)
            {
                $order->addStatusHistoryComment('Bedrag komt niet overeen. Order bedrag: ' . ($orderAmount / 100) . ' Betaald: ' . ($paidAmount / 100));
            }
            $payment = $order->getPayment();
            $payment instanceof Mage_Sales_Model_Order_Payment;
            $autoInvoice = $store->getConfig('pay_payment/general/auto_invoice');
            $invoiceEmail = $store->getConfig('pay_payment/general/invoice_email');

            if ($autoInvoice)
            {
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

                    if (!$order->canInvoice())
                    {
                        die('Cannot create an invoice.');
                    }

                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

                    if (!$invoice->getTotalQty())
                    {
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

                if ($invoiceEmail)
                {
                    $invoice->sendEmail();
                    $invoice->setEmailSent(true);
                    $invoice->save();
                }
            }


            // ingestelde status ophalen
            $processedStatus = $store->getConfig('payment/' . $payment->getMethod() . '/order_status_success');
//            $processedStatus = Mage::getStoreConfig('payment/' . $payment->getMethod() . '/order_status_success', $store);

            $order->setIsInProcess(true);

            $stateMessage = 'Betaling ontvangen, klantkenmerk: ' . $endUserId;

            $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING, $processedStatus, $stateMessage, true
            );
            $order->save();

            $sendMail = $store->getConfig('payment/' . $payment->getMethod() . '/send_mail');
            $sendStatusupdates = $store->getConfig('pay_payment/general/send_statusupdates');

            if ($sendMail == 'start')
            {
                // De bevestigingsmail is al verstuurd, we gaan alleen de update sturen
                if ($sendStatusupdates)
                {
                    $order->sendOrderUpdateEmail();
                }
            } else
            {
                // De bevestigingsmail is nog niet verstuurd, dus doen we het nu
                if (!$order->getEmailSent())
                {
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
        } elseif ($status == Pay_Payment_Model_Transaction::STATE_CANCELED)
        {

            /** @var $order Mage_Sales_Model_Order */
            if ($order->getTotalDue() <= 0 || $transaction->getStatus() == Pay_Payment_Model_Transaction::STATE_SUCCESS)
            {
                throw Mage::exception('Pay_Payment', 'Cannot cancel already paid order');
            }

            // order annuleren
            //$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED);
            $order->cancel();
            $order->save();
            $sendStatusupdates = $store->getConfig('pay_payment/general/send_statusupdates');
            if ($sendStatusupdates)
            {
                $order->sendOrderUpdateEmail();
            }
            // transactie in pay tabel updaten
            $transaction->setStatus($status);
            $transaction->setLastUpdate(time());
            $transaction->save();

            return true;
        } else
        {
            throw Mage::exception('Pay_Payment', 'Unknown status ' . $status, 1);
        }
    }


    public function getTransactionInfo($transactionId, $store = null)
    {
        if ($store == null)
        {
            $store = Mage::app()->getStore();
        }

        $helperApi = Mage::helper('pay_payment/api_info');
        /* @var $helperApi Pay_Payment_Helper_Api_Info */


        $apiToken = $store->getConfig('pay_payment/general/apitoken');


        $helperApi->setApiToken($apiToken);
        $helperApi->setTransactionId($transactionId);

        $transactionInfo = $helperApi->doRequest();

        return $transactionInfo;
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
        if ($store == null)
        {
            $store = Mage::app()->getStore();
        }

        $transactionInfo = $this->getTransactionInfo($transactionId, $store);

        $status = Pay_Payment_Model_Transaction::STATE_PENDING;

        //status bepalen
        if ($transactionInfo['paymentDetails']['state'] == 100)
        {
            $status = Pay_Payment_Model_Transaction::STATE_SUCCESS;
        } elseif ($transactionInfo['paymentDetails']['state'] < 0)
        {
            $status = Pay_Payment_Model_Transaction::STATE_CANCELED;
        } else
        {
            $status = Pay_Payment_Model_Transaction::STATE_PENDING;
            //we gaan geen update doen
            return;
        }
        $paidAmount = $transactionInfo['paymentDetails']['paidAmount'];
        $endUserId = $transactionInfo['paymentDetails']['identifierPublic'];

        // alle data is opgehaald status updaten
        $this->updateState($transactionId, $status, $paidAmount, $endUserId, $store);

        return $status;
    }

    public function getTransactionStatus($transactionId, $store = null)
    {
        $transactionInfo = $this->getTransactionInfo($transactionId, $store);

        $status = Pay_Payment_Model_Transaction::STATE_PENDING;

        //status bepalen
        if ($transactionInfo['paymentDetails']['state'] == 100)
        {
            return Pay_Payment_Model_Transaction::STATE_SUCCESS;
        } elseif ($transactionInfo['paymentDetails']['state'] < 0)
        {
            return Pay_Payment_Model_Transaction::STATE_CANCELED;
        } else
        {
            return Pay_Payment_Model_Transaction::STATE_PENDING;
        }
    }

}
