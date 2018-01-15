<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Setup;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Ebizmarts\SagePaySuite\Setup\SplitDatabaseConnectionProvider;

class UpgradeSchemaTest extends \PHPUnit_Framework_TestCase
{
    public function testUpgrade()
    {
        $tableMock = $this
            ->getMockBuilder('Magento\Framework\DB\Ddl\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $tableMock->expects($this->exactly(10))
            ->method('addColumn')
            ->willReturnSelf();
        $tableMock->expects($this->once())
            ->method('addIndex')

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

        $connectionProviderMock = $this->getMockBuilder(SplitDatabaseConnectionProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connectionProviderMock
            ->expects($this->once())
            ->method("getSalesConnection")
            ->with($schemaSetupMock)
            ->willReturn($connectionMock);
        $objectManagerHelper = new ObjectManager($this);
        $upgradeSchema = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Setup\UpgradeSchema',
            [
                "connectionProvider" => $connectionProviderMock
            ]
        );
        $upgradeSchema->upgrade($schemaSetupMock, $moduleContextMock);
    }
}
