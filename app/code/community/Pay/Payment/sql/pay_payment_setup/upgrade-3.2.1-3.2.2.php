<?php
/* @var $installer Mage_Sales_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('pay_payment/transaction');
$tableNameSalesOrder = $installer->getTable('sales/order');

if ($installer->getConnection()->isTableExists($tableName)) {
    $installer->getConnection()            
            ->dropForeignKey($tableName, $installer->getFkName($tableName, 'order_id', $tableNameSalesOrder, 'entity_id'))
            ->addForeignKey($installer->getFkName($tableName, 'order_id', $tableNameSalesOrder, 'entity_id'), $tableName, 'order_id', $tableNameSalesOrder, 'entity_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE);
}
$installer->endSetup();