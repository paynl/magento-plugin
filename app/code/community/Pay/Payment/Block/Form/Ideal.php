<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Pay_Payment_Block_Form_Ideal extends Pay_Payment_Block_Form_Abstract {
    protected $paymentMethodId = 10;
    protected $paymentMethodName = 'iDEAL';
    protected $methodCode = 'pay_payment_ideal';

    protected $template = 'pay/payment/form/default.phtml';

    /**
     * Constructor. Set template.
     */
    protected function _construct() {
        $bankSelectType = Mage::getStoreConfig('payment/pay_payment_ideal/bank_select_type', Mage::app()->getStore());

        if ($bankSelectType == 'radio') {
            $this->template = 'pay/payment/form/ideal.phtml';
        } elseif($bankSelectType == 'select') {
            $this->template = 'pay/payment/form/idealSelect.phtml';
        } else {
            $this->template = 'pay/payment/form/default.phtml';
        }

        return  parent::_construct();
    }

    protected function getBanks() {
        $helper = Mage::helper('pay_payment');
        $option = $helper->getOption(Pay_Payment_Model_PaymentMethod_Ideal::OPTION_ID);
        $banks = $option->getSubs();
        return $banks;
    }

}
