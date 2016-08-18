<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\ResourceModel;

class TokenTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\ResourceModel\Token
     */
    protected $resourceTokenModel;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    protected function setUp()
    {
        $selectMock = $this
            ->getMockBuilder('Magento\Framework\DB\Select')
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('from')
            ->willReturnSelf();
        $selectMock->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $selectMock->expects($this->any())
            ->method('limit')
            ->willReturnSelf();

        $this->connectionMock = $this
            ->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($selectMock);

        $resourceMock = $this
            ->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connectionMock));

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->resourceTokenModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\ResourceModel\Token',
            [
                "resource" => $resourceMock,
            ]
        );
    }

    public function testGetCustomerTokens()
    {
        $this->connectionMock->expects($this->any())
            ->method('fetchAll')
            ->willReturn((object)[
                [
                    "token_id" => 1
                ],
                [
                    "token_id" => 2
                ]
            ]);

        $tokenModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Token')
            ->disableOriginalConstructor()
            ->getMock();

        $tokenModelMock->expects($this->once())
            ->method('setData')
            ->with((object)[
                [
                    "token_id" => 1
                ],
                [
                    "token_id" => 2
                ]
            ]);

        $this->resourceTokenModel->getCustomerTokens($tokenModelMock, 1, 'testebizmarts');
    }

    public function testGetTokenById()
    {
        $this->connectionMock->expects($this->any())
            ->method('fetchRow')
            ->willReturn((object)[
                "token_id" => 1
            ]);

        $this->assertEquals(
            (object)[
                "token_id" => 1
            ],
            $this->resourceTokenModel->getTokenById(1)
        );
    }

    public function testIsTokenOwnedByCustomer()
    {
        $this->connectionMock->expects($this->any())
            ->method('fetchAll')
            ->willReturn((object)[
                [
                    "token_id" => 1
                ]
            ]);

        $this->assertEquals(
            true,
            $this->resourceTokenModel->isTokenOwnedByCustomer(1, 1)
        );
    }
}
