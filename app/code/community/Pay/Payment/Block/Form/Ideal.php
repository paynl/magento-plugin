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
}
