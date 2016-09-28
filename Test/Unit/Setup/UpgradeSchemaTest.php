<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Setup;

class UpgradeSchemaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Setup\UpgradeSchema
     */
    private $upgradeSchema;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->upgradeSchema = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Setup\UpgradeSchema',
            []
        );
    }
    // @codingStandardsIgnoreEnd

    public function testUpgrade()
    {
        $tableMock = $this
            ->getMockBuilder('Magento\Framework\DB\Ddl\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $tableMock->expects($this->atLeastOnce())
            ->method('addColumn')
            ->willReturnSelf();

        $connectionMock = $this
            ->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock->expects($this->once())
            ->method('newTable')
            ->willReturn($tableMock);
        $connectionMock->expects($this->once())
            ->method('createTable');

        $schemaSetupMock = $this
            ->getMockBuilder('Magento\Framework\Setup\SchemaSetupInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $schemaSetupMock->expects($this->once())
            ->method('startSetup');
        $schemaSetupMock->expects($this->once())
            ->method('endSetup');
        $schemaSetupMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $moduleContextMock = $this
            ->getMockBuilder('Magento\Framework\Setup\ModuleContextInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $schemaSetupMock->expects($this->at(0))
            ->method('1.0.0')
            ->willReturn($connectionMock);
        $schemaSetupMock->expects($this->at(0))
            ->method('1.1.0')
            ->willReturn($connectionMock);

        $this->upgradeSchema->upgrade($schemaSetupMock, $moduleContextMock);
    }
}
