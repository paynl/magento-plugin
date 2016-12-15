<?php

class Pay_Payment_CheckoutController extends Mage_Core_Controller_Front_Action
{

    public function redirectAction()
    {
        Mage::log('Starting transaction', null, 'paynl.log');
        $helper = Mage::helper('pay_payment');
        $session = Mage::getSingleton('checkout/session');
        /* @var $session Mage_Checkout_Model_Session */
        if ($session->getLastRealOrderId()) {
            Mage::log('Order found in session, orderId: ' . $session->getLastRealOrderId(), null, 'paynl.log');

            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());


            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $order->getPayment();

            $additionalData = array();

            if($session->getIban()){
                $additionalData['iban'] = $session->getIban();
            }
            if($session->getOptionSubId()){
                $additionalData['optionSubId'] = $session->getOptionSubId();
            }

            $birthdayDay = $session->getBirthdayDay();
            $birthdayMonth = $session->getBirthdayMonth();
            $birthdayYear = $session->getBirthdayYear();

            if(!empty($birthdayDay) && !empty($birthdayMonth) &&!empty($birthdayYear)){
                $birthDate = $birthdayYear.'-'.$birthdayMonth.'-'.$birthdayDay;
                $order->setCustomerDob($birthDate);
                $order->save();
            }

            $payment->setAdditionalData($additionalData);

            /** @var Pay_Payment_Model_Paymentmethod $method */
            $method = $order->getPayment()->getMethodInstance();

            if ($order->getId()) {
                $data = $method->startPayment($order);

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
