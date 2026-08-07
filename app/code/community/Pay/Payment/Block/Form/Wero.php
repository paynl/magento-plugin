<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Pay_Payment_Block_Form_Wero extends Pay_Payment_Block_Form_Abstract {
    protected $paymentMethodId = 3762;
    protected $paymentMethodName = 'WERO';
    protected $methodCode = 'pay_payment_wero';
    protected $template = 'pay/payment/form/default.phtml';
}
