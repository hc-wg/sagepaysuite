<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Setup;

class InstallSchemaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Setup\InstallSchema
     */
    private $installSchema;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->installSchema = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Setup\InstallSchema',
            []
        );
    }
    // @codingStandardsIgnoreEnd

    public function testInstall()
    {
        $connectionMock = $this
            ->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock->expects($this->once())
            ->method('modifyColumn')
            ->with(
                'sales_order_payment',
                "last_trans_id",
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 100,
                    'nullable' => false
                ]
            );

        $schemaSetupMock = $this
            ->getMockBuilder('Magento\Framework\Setup\SchemaSetupInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $schemaSetupMock->expects($this->once())
            ->method('startSetup');
        $schemaSetupMock->expects($this->once())
            ->method('endSetup');
        $schemaSetupMock->expects($this->once())
            ->method('getTable')
            ->with('sales_order_payment')
            ->willReturn('sales_order_payment');
        $schemaSetupMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $moduleContextMock = $this
            ->getMockBuilder('Magento\Framework\Setup\ModuleContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->installSchema->install($schemaSetupMock, $moduleContextMock);
    }
}
