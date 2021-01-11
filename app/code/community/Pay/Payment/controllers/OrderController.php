<?php

class Pay_Payment_OrderController extends Mage_Core_Controller_Front_Action
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

    public function returnAction()
    {
        $params = $this->getRequest()->getParams();

        $transactionId = $params['orderId'];

        $status = $this->helperOrder->getTransactionStatus($transactionId);
        $order = $this->helperOrder->getOrderByTransactionId($transactionId);
        $store = $order->getStore();

        $extended_logging = Mage::getStoreConfig('pay_payment/general/extended_logging', $store);

        $pageSuccess = $store->getConfig('pay_payment/general/page_success');
        $pagePending = $store->getConfig('pay_payment/general/page_pending');
        $pageCanceled = $store->getConfig('pay_payment/general/page_canceled');

	    /**
	     * @var $orderPayment Mage_Sales_Model_Order_Payment
	     */
	    $orderPayment = $order->getPayment();
	    $hash = $orderPayment->getAdditionalInformation( 'paynl_hash');
	    if(!empty($hash)){
	    	$instoreStatus = \Paynl\Instore::status(array(
	    		'hash' => $hash
		    ));
	    	$state = $instoreStatus->getTransactionState();
	    	if($state == 'approved'){
	    		$status = Pay_Payment_Model_Transaction::STATE_SUCCESS;
		    } else {
			    $status = Pay_Payment_Model_Transaction::STATE_CANCELED;
		    }
	    }

        if ($status == Pay_Payment_Model_Transaction::STATE_CANCELED) {
            Mage::getSingleton('checkout/session')->addNotice($this->__('Betaling geannuleerd'));
        }

	    $quoteModel = Mage::getModel('sales/quote');
	    $quoteId = $order->getQuoteId();

	    /**
	     * @var $quote Mage_Sales_Model_Quote
	     */
	    $quote = $quoteModel->load($quoteId);

        if($extended_logging) $order->addStatusHistoryComment('Customer returned from payment page, status is: '.$status);
        $order->save();

        if ($status == Pay_Payment_Model_Transaction::STATE_SUCCESS) {
	        $quote->setIsActive(false)->save();
            $this->_redirect($pageSuccess, array('_query' => array('utm_nooverride' => 1)));
        } elseif ($status == Pay_Payment_Model_Transaction::STATE_PENDING) {
	        $quote->setIsActive(false)->save();
            $this->_redirect($pagePending,  array('_query' => array('utm_nooverride' => 1)));
        } else {
            $restoreCart = Mage::getStoreConfig('pay_payment/general/restore_cart', $order->getStore());
            if ($restoreCart) {
                $quote->setIsActive(true)->save();
            }

            $this->_redirect($pageCanceled);
        }
    }

    public function exchangeAction()
    {
        $error = false;
        $params = $this->getRequest()->getParams();

        $action = $params['action'];
        $transactionId = $params['order_id'];
        if (empty($transactionId)) {
            $get = $this->getRequest()->setParamSources(array('_GET'))->getParams();
            $post = $this->getRequest()->setParamSources(array('_POST'))->getParams();

            if (!empty($get['order_id'])) {
                $transactionId = $get['order_id'];
                Mage::log('Error in exchange, but fixed by getting only the _GET var ', null, 'exchange.log');
            } elseif (!empty($post['order_id'])) {
                $transactionId = $post['order_id'];
                Mage::log('Error in exchange, but fixed by getting only the _POST var ', null, 'exchange.log');
            } else {
                Mage::log('Error in exchange, cannot find orderId in _GET or _POST ', null, 'exchange.log');
            }

            $get = $this->getRequest()->setParamSources(array('_GET'))->getParams();
            $post = $this->getRequest()->setParamSources(array('_POST'))->getParams();
            Mage::log('_GET was: ' . json_encode($get), null, 'exchange.log');
            Mage::log('_POST was: ' . json_encode($post), null, 'exchange.log');
        }
        if(empty($transactionId)){
            die('Error: TransactionId not found in _GET or _POST');
        }

        try {

            if ($params['action'] == 'pending') {
                throw Mage::exception('Pay_Payment', 'Ignoring pending', 0);
            }
            if (substr($params['action'],0,6) == 'refund') {
                throw Mage::exception('Pay_Payment', 'Ignoring refund', 0);
            }
          
            $payTransaction = $this->helperData->lockTransaction($transactionId);

            $status = $this->helperOrder->processByTransactionId($transactionId, null, $payTransaction);
          
            $this->helperData->removeLock($transactionId, $payTransaction);
  
            $resultMsg = 'Status updated to ' . $status;
        } catch (Pay_Payment_Model_Transaction_LockException $e) {
            $error = true;
            $resultMsg = 'ERROR EX1: ';
            $resultMsg .= $e->getMessage();
        } catch (Pay_Payment_Exception $e) {
            if ($e->getCode() == 0) {
                $resultMsg = 'NOTICE: ';
            } else {
                $error = true;
                $resultMsg = 'ERROR EX2: ';
            }

            $this->helperData->removeLock($transactionId);
            $resultMsg .= $e->getMessage();

        } catch (Exception $e) {
            $error = true;
            $resultMsg = 'ERROR EX3: ' . $e->getMessage();
            $this->helperData->removeLock($transactionId);
        }

        if ($error) {
            echo "FALSE|" . $resultMsg;
        } else {
            echo "TRUE|" . $resultMsg;
        }

        die();
    }

}
