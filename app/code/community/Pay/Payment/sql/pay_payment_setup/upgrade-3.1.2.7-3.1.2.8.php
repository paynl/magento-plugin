<?php
/* @var $installer Mage_Sales_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('pay_payment/transaction');
$tableNameSalesOrder = $installer->getTable('sales/order');

if ($installer->getConnection()->isTableExists($tableName)) {
    //drop unique index op transaction order_id we maken daar een gewone index van, omdat het kan voorkomen dat een order vaker een transactie start
    $installer->getConnection()->addIndex($tableName, $installer->getIdxName($tableName, array('order_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX), 'order_id', Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX);
    $installer->getConnection()->dropIndex($tableName, $installer->getIdxName($tableName, array('order_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE));        
}
$installer->endSetup();