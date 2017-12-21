<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /** @var SplitDatabaseConnectionProvider */
    private $connectionProvider;

    public function __construct(SplitDatabaseConnectionProvider $connectionProvider)
    {
        $this->connectionProvider = $connectionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * CURRENT VERSION LOWER THAN 1.1.0
         */
        if (version_compare($context->getVersion(), '1.1.0') == -1) {
            $this->createSagePaySuiteTokenTable($installer);
        }

        /**
         * CURRENT VERSION LOWER THAN 1.1.1
         */
        if (version_compare($context->getVersion(), '1.1.1') == -1) {
            $this
                ->connectionProvider
                ->getSalesConnection($setup)
                ->addColumn(
                    $setup->getTable('sales_payment_transaction'),
                "sagepaysuite_fraud_check",
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'comment' => 'Sage Pay Fraud Check Flag'
                ]
            );
        }

        $installer->endSetup();
    }

    /**
     * @param $installer
     */
    private function createSagePaySuiteTokenTable(SchemaSetupInterface $installer)
    {
        $table = $installer->getConnection()->newTable($installer->getTable('sagepaysuite_token'))->addColumn('id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
                ['nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Token Id')->addColumn('customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
                ['unsigned' => true, 'nullable' => false], 'Customer Id')->addColumn('token',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 150, ['nullable' => false], 'Token')->addColumn('cc_last_4',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 100, [], 'Cc Last 4')->addColumn('cc_exp_month',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 12, [], 'Cc Exp Month')->addColumn('cc_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 32, [], 'Cc Type')->addColumn('cc_exp_year',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 4, [], 'Cc Exp Year')->addColumn('vendorname',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 100, [], 'Vendorname')->addColumn('created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At')->addColumn('store_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, null,
                ['unsigned' => true], 'Store Id')->addIndex($installer->getIdxName('sagepaysuite_token',
                ['customer_id']), ['customer_id']);

        $installer->getConnection()->createTable($table);
    }
}
