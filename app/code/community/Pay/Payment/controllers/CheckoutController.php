<?php

class Pay_Payment_CheckoutController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Pay_Payment_Helper_Order
     */
    private $helperOrder;
    /**
     * @var Pay_Payment_Helper_Data
     */
    private $helperData;

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        $this->helperData = Mage::helper('pay_payment');
        $this->helperOrder = Mage::helper('pay_payment/order');


        parent::__construct($request, $response, $invokeArgs);
    }

    public function redirectAction()
    {
        Mage::log('Starting transaction', null, 'paynl.log');

        /**
         * @var $session Mage_Checkout_Model_Session
         */
        $session = Mage::getSingleton('checkout/session');

        if ($session->getLastRealOrderId()) {
            Mage::log('Order found in session, orderId: ' . $session->getLastRealOrderId(), null, 'paynl.log');

            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

            $restoreCart = Mage::getStoreConfig('pay_payment/general/restore_cart', $order->getStore());
            $extended_logging = Mage::getStoreConfig('pay_payment/general/extended_logging', $order->getStore());

            if ($extended_logging) $order->addStatusHistoryComment('Starting transaction from checkout controller');

            if ($restoreCart) {
                $quoteModel = Mage::getModel('sales/quote');
                $quoteId = $order->getQuoteId();

                /**
                 * @var $quote Mage_Sales_Model_Quote
                 */
                $quote = $quoteModel->load($quoteId);

                $quote->setIsActive(true)->setReservedOrderId(null)->save();
                Mage::getSingleton('checkout/session')->replaceQuote($quote);
            }

            /** @var Pay_Payment_Model_Paymentmethod $method */
            $method = $order->getPayment()->getMethodInstance();

            if ($order->getId()) {
                $data = $method->startPayment($order);

                if ($extended_logging) $order->addStatusHistoryComment('Payment started. Redirecting user');
                $order->save();
                Mage::app()->getResponse()->setRedirect($data['url']);
            } else {
                // loading order failed
                Mage::log('Error: OrderId found in session but loading the order failed, orderId:' . $session->getLastRealOrderId(), null, 'paynl.log');
            }
        } else {
            // no orderId in session
            Mage::log('Error: No order found in the session, so i cannot create a payment', null, 'paynl.log');
        }
    }

}
