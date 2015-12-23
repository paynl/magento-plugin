<?php
class Pay_Payment_Model_Paymentmethod_Billink extends Pay_Payment_Model_Paymentmethod {
    const OPTION_ID = 1672;
    protected $_paymentOptionId = 1672;
    protected $_code = 'pay_payment_billink';
    protected $_formBlockType = 'pay_payment/form_billink';

   
     public function assignData($data) {
        $store = Mage::app()->getStore();

        $session = Mage::getSingleton('checkout/session');
        /* @var $session Mage_Checkout_Model_Session */
        $session->setBillinkAgree(0);
        $session->setBirthdayDay('');
        $session->setBirthdayMonth('');
        $session->setBirthdayYear('');
        $session->setKvknummer('');


        $enablePersonal = Mage::getStoreConfig('payment/pay_payment_billink/ask_data_personal', Mage::app()->getStore());
        $enableBusiness = Mage::getStoreConfig('payment/pay_payment_billink/ask_data_business', Mage::app()->getStore());
        if($enablePersonal == 0 && $enableBusiness == 0){
            return parent::assignData($data);
        }

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }       
       
        if($data->getBillinkAgree() == 1){
            $session->setBillinkAgree(1);
        } else {
            $session->setBillinkAgree(0);
            Mage::throwException(Mage::helper('payment')->__('Om met billink te betalen, moet je akkoord gaan met de voorwaarden'));
        }

        if($enablePersonal == 1 && ($enableBusiness == 0 || $data->getTypeOrder() == 'p')){
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
        }
        if($enableBusiness == 1 && ($enablePersonal == 0 || $data->getTypeOrder() == 'b')){
            if($data->getKvknummer()){
                $kvknummer = $data->getKvknummer();
                $session->setKvknummer($kvknummer);
            }          
        }
        return $this;
    }
}
    