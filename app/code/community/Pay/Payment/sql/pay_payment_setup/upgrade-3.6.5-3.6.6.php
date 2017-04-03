<?php
/* @var $installer Mage_Sales_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('pay_payment/transaction');

if ($installer->getConnection()->isTableExists($tableName)) {
    $installer->getConnection()->addColumn($tableName, 'lock_date', array(
        'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
        'nullable' => true,
        'comment' => 'The plugin uses this column to put a lock on processing this transaction, after processing this field should be null'
    ));
}

$installer->endSetup();