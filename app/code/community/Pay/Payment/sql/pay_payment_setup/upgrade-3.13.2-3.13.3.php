<?php
/* @var $installer Mage_Sales_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('pay_payment/transaction');

if ($installer->getConnection()->isTableExists($tableName)) {
    $installer->getConnection()->addColumn($tableName, 'gateway_url', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 255,
        'nullable' => true,
        'comment' => 'The url of the gateway used for this transaction'
    ));
}

$installer->endSetup();