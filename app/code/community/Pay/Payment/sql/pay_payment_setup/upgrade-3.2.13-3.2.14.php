<?php
/* @var $installer Mage_Sales_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

// remove the old sofortbanking config data
$installer->deleteConfigData('payment/pay_payment_directebankingat/active');
$installer->deleteConfigData('payment/pay_payment_directebankingbe/active');
$installer->deleteConfigData('payment/pay_payment_directebankingch/active');
$installer->deleteConfigData('payment/pay_payment_directebankingde/active');
$installer->deleteConfigData('payment/pay_payment_directebankinggb/active');
$installer->deleteConfigData('payment/pay_payment_directebankingnl/active');


$installer->endSetup();