<?php
class Pay_Payment_Model_Paymentmethod_Focum extends Pay_Payment_Model_Paymentmethod {
    const OPTION_ID = 1702;
    protected $_paymentOptionId = 1702;
    protected $_code = 'pay_payment_focum';
    protected $_formBlockType = 'pay_payment/form_focum';
    public function assignData($data) {
        $store = Mage::app()->getStore();

        $session = Mage::getSingleton('checkout/session');
        /* @var $session Mage_Checkout_Model_Session */
        $session->setBirthdayDay('');
        $session->setBirthdayMonth('');
        $session->setBirthdayYear('');
        $session->setIban('');

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        if ($data->getBirthdayDay()) {
            $birthdayDay = $data->getBirthdayDay();
            $session->setBirthdayDay($birthdayDay);
        }
        if ($data->getBirthdayMonth()) {
            $birthdayMonth = $data->getBirthdayMonth();
            $session->setBirthdayMonth($birthdayMonth);
        }
        if ($data->getBirthdayYear()) {
            $birthdayYear = $data->getBirthdayYear();
            $session->setBirthdayYear($birthdayYear);
        }

        if($data->getIban()){
            $iban = $data->getIban();
            $session->setIban($iban);
        }

        return $this;
    }
}
    