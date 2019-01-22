<?php
class Pay_Payment_Model_Paymentmethod_CapayableGespreid extends Pay_Payment_Model_Paymentmethod {
    const OPTION_ID = 1813;
    protected $_paymentOptionId = 1813;
    protected $_code = 'pay_payment_capayablegespreid';
    protected $_formBlockType = 'pay_payment/form_capayableGespreid';

    protected static function getFirstname($address){
        return $address->getFirstname();
    }
}
    