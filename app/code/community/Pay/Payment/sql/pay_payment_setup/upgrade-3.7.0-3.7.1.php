<?php
/* @var $installer Mage_Sales_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$installer->addAttribute('quote_address', 'payment_charge_tax_amount', array('type' => 'decimal'));
$installer->addAttribute('quote_address', 'base_payment_charge_tax_amount', array('type' => 'decimal'));

$installer->addAttribute('order', 'payment_charge_tax_amount', array('type' => 'decimal'));
$installer->addAttribute('order', 'base_payment_charge_tax_amount', array('type' => 'decimal'));

$installer->addAttribute('invoice', 'payment_charge_tax_amount', array('type' => 'decimal'));
$installer->addAttribute('invoice', 'base_payment_charge_tax_amount', array('type' => 'decimal'));

$installer->addAttribute('creditmemo', 'payment_charge_tax_amount', array('type' => 'decimal'));
$installer->addAttribute('creditmemo', 'base_payment_charge_tax_amount', array('type' => 'decimal'));

$installer->endSetup();