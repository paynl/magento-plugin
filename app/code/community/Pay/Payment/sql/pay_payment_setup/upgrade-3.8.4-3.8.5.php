<?php
/* @var $installer Mage_Sales_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$installer->addAttribute('quote_address', 'paynl_payment_charge', array('type' => 'decimal'));
$installer->addAttribute('quote_address', 'paynl_base_payment_charge', array('type' => 'decimal'));
$installer->addAttribute('quote_address', 'paynl_payment_charge_tax_amount', array('type' => 'decimal'));
$installer->addAttribute('quote_address', 'paynl_base_payment_charge_tax_amount', array('type' => 'decimal'));

$installer->addAttribute('order', 'paynl_payment_charge', array('type' => 'decimal'));
$installer->addAttribute('order', 'paynl_base_payment_charge', array('type' => 'decimal'));
$installer->addAttribute('order', 'paynl_payment_charge_tax_amount', array('type' => 'decimal'));
$installer->addAttribute('order', 'paynl_base_payment_charge_tax_amount', array('type' => 'decimal'));

$installer->addAttribute('invoice', 'paynl_payment_charge', array('type' => 'decimal'));
$installer->addAttribute('invoice', 'paynl_base_payment_charge', array('type' => 'decimal'));
$installer->addAttribute('invoice', 'paynl_payment_charge_tax_amount', array('type' => 'decimal'));
$installer->addAttribute('invoice', 'paynl_base_payment_charge_tax_amount', array('type' => 'decimal'));

$installer->addAttribute('creditmemo', 'paynl_payment_charge', array('type' => 'decimal'));
$installer->addAttribute('creditmemo', 'paynl_base_payment_charge', array('type' => 'decimal'));
$installer->addAttribute('creditmemo', 'paynl_payment_charge_tax_amount', array('type' => 'decimal'));
$installer->addAttribute('creditmemo', 'paynl_base_payment_charge_tax_amount', array('type' => 'decimal'));

$installer->endSetup();