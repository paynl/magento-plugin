<?php
/* @var $installer Mage_Sales_Model_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('pay_payment/optionsub');

if ($installer->getConnection()->isTableExists($tableName)) {
    $installer->getConnection()->modifyColumn($tableName, 'option_sub_id', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable' => false,
        'length' => 100
    ));
}

$installer->endSetup();