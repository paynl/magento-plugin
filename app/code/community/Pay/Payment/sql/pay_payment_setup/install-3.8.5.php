<?php

/* @var $installer Pay_Payment_Model_Resource_Setup */
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
//create the pay_payment/option table
$tableName = $installer->getTable('pay_payment/option');

// Check if the table already exists
if (!$installer->getConnection()->isTableExists($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn('internal_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ), 'Payment option Id')
        ->addColumn('option_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => false,
            'unsigned' => true,
            'nullable' => false,
        ), 'Payment option Id')
        ->addColumn('service_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 12, array(
            'nullable' => false,
        ), 'Service Id (SL-xxxx-xxxx)')
        ->addColumn('name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'Payment option name')
        ->addColumn('image', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'The url to the icon image')
        ->addColumn('update_date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable' => false
        ), 'The datetime this payment option was refreshed')
        ->addIndex($installer->getIdxName($tableName, array(
            'option_id',
            'service_id'
        ), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE), array('option_id', 'service_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE));

    $installer->getConnection()->createTable($table);
}

//create the pay_payment/optionsub table
$tableName = $installer->getTable('pay_payment/optionsub');

// Check if the table already exists
if (!$installer->getConnection()->isTableExists($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn('internal_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ), 'Id')
        ->addColumn('option_sub_id', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable' => false,
        ), 'Payment option sub Id')
        ->addColumn('option_internal_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
        ), 'Link to the payment option')
        ->addColumn('name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'The name of the option sub')
        ->addColumn('image', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false), 'The url to the icon image')
        ->addColumn('active', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array('nullable' => false), 'OptionSub  active or not')
        ->addIndex($installer->getIdxName($tableName, array('option_internal_id')), array('option_internal_id'))
        ->addIndex($installer->getIdxName($tableName, array('option_sub_id', 'option_internal_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE), array('option_sub_id', 'option_internal_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
        ->addForeignKey($installer->getFkName($tableName, 'option_internal_id', 'pay_payment/option', 'internal_id'), 'option_internal_id', $installer->getTable('pay_payment/option'), 'internal_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE);
    $installer->getConnection()->createTable($table);
}
//create the pay_payment/optionsub table
$tableName = $installer->getTable('pay_payment/transaction');

$tableNameSalesOrder = $installer->getTable('sales/order');

// Check if the table already exists
if (!$installer->getConnection()->isTableExists($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
            'primary' => true,
            'identity' => true,
            'unsigned' => true,
        ))
        ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(
            'nullable' => false,
        ), 'The transaction id, generated by PAY.')
        ->addColumn('service_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 12, array(
            'nullable' => false,
        ), 'Service Id (SL-xxxx-xxxx)')
        ->addColumn('option_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false
        ), 'The payment option id')
        ->addColumn('option_sub_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => true
        ), 'The payment option sub id')
        ->addColumn('amount', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => false,
            'nullable' => false
        ), 'The total amount in cents')
        ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false
        ), 'The order entity_id (references: sales_flat_order.entity_id)')
        ->addColumn('status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array('nullable' => false), 'The status of the transaction')
        ->addColumn('created', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => false), 'The datetime the transaction was created')
        ->addColumn('last_update', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => false), 'The datetime the transaction was last updated')
        ->addColumn('lock_date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => true), 'The plugin uses this column to put a lock on processing this transaction, after processing this field should be null')
        ->addIndex($installer->getIdxName($tableName, array('order_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX), array('order_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX))
        ->addForeignKey(
            $installer->getFkName($tableName, 'order_id', $tableNameSalesOrder, 'entity_id'),
            'order_id',
            $tableNameSalesOrder,
            'entity_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE,
            Varien_Db_Ddl_Table::ACTION_CASCADE);

    $installer->getConnection()->createTable($table);
}


$installer->endSetup();
