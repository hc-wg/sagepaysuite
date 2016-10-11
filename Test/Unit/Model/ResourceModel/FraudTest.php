<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\ResourceModel;

class FraudTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOrdersToCancel()
    {
        $selectMock = $this
            ->getMockBuilder('Magento\Framework\DB\Select')
            ->setMethods(['from', 'where', 'limit'])
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock
            ->expects($this->once())
            ->method('from')
            ->with('sales_order', 'entity_id')
            ->willReturnSelf();
        $selectMock
            ->expects($this->exactly(3))
            ->method('where')
            ->withConsecutive(
                ['state=?', 'pending_payment'],
                ['created_at <= now() - INTERVAL 15 MINUTE'],
                ['created_at >= now() - INTERVAL 2 DAY']
            )
            ->willReturnSelf();
        $selectMock
            ->expects($this->once())
            ->method('limit')
            ->with(10)
            ->willReturnSelf();

        $queryMock = $this
            ->getMockBuilder(\Magento\Framework\DB\Statement\Pdo\Mysql::class)
            ->setMethods(['fetchColumn'])
            ->disableOriginalConstructor()
            ->getMock();
        $queryMock
            ->expects($this->exactly(2))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls(198, false);

        $connectionMock = $this
            ->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);
        $connectionMock
            ->expects($this->once())
            ->method('query')
            ->with($selectMock)
            ->willReturn($queryMock);

        $resourceMock = $this
            ->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $fraudModelMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud::class)
            ->setMethods(['getTable', 'getConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $fraudModelMock
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);
        $fraudModelMock
            ->expects($this->once())
            ->method('getTable')
            ->with('sales_order')
            ->willReturn('sales_order');

        $this->assertEquals([198], $fraudModelMock->getOrdersToCancel());
    }
}
