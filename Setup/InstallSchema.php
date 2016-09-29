<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $myContext = $context;
        $installer = $setup;

        /**
         * Prepare database for install
         */
        $installer->startSetup();

        // Get module table
        $tableName = $setup->getTable('sales_order_payment');

        $connection = $setup->getConnection();

        $connection->modifyColumn(
            $tableName,
            "last_trans_id",
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 100,
                'nullable' => false
            ]
        );

        $installer->endSetup();
    }
}
