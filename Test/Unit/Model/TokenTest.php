<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class TokenTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Token
     */
    private $tokenModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post|\PHPUnit_Framework_MockObject_MockObject
     */
    private $postApiMock;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->resourceMock = $this
            ->getMockBuilder('Magento\Framework\Model\ResourceModel\Db\AbstractDb')
            ->setMethods(["getIdFieldName", "_construct", "getConnection", "save",
                "getCustomerTokens", "getTokenById", "isTokenOwnedByCustomer"])
            ->disableOriginalConstructor()
            ->getMock();

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->postApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Post')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token',
            [
                'resource' => $this->resourceMock,
                "config" => $configMock,
                "postApi" => $this->postApiMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testSaveToken()
    {
        $token = $this->tokenModel->saveToken(
            1,
            'fsd587fds78dfsfdsa687dsa',
            'VISA',
            '0006',
            '02',
            '22',
            'testebizmarts'
        );

        $this->assertEquals(
            'fsd587fds78dfsfdsa687dsa',
            $token->getToken()
        );

        $this->assertEquals(
            'testebizmarts',
            $token->getVendorname()
        );
    }

    public function testGetCustomerTokens()
    {
        $this->assertEquals(
            [],
            $this->tokenModel->getCustomerTokens(1, 'testebizmarts')
        );
    }

    public function testDeleteToken()
    {
        $token = $this->tokenModel->saveToken(
            1,
            'fsd587fds78dfsfdsa687dsa',
            'VISA',
            '0006',
            '02',
            '22',
            'testebizmarts'
        );

        $this->postApiMock->expects($this->once())
            ->method('sendPost')
            ->with(
                [
                "VPSProtocol" => null,
                "TxType" => "REMOVETOKEN",
                "Vendor" => 'testebizmarts',
                "Token" => 'fsd587fds78dfsfdsa687dsa'
                ],
                \Ebizmarts\SagePaySuite\Model\Config::URL_TOKEN_POST_REMOVE_TEST,
                ["OK"]
            );

        $token->deleteToken();
    }

    public function testLoadToken()
    {
        $this->resourceMock->expects($this->any())
            ->method('getTokenById')
            ->will($this->returnValue([
                "id" => 1,
                "customer_id" => 1,
                "token" => 'fsd587fds78dfsfdsa687dsa',
                "cc_type" => 'VISA',
                "cc_last_4" => '0006',
                "cc_exp_month" => '02',
                "cc_exp_year" => '22',
                "vendorname" => 'testebizmarts',
                "created_at" => '',
                "store_id" => 1
            ]));

        $token = $this->tokenModel->loadToken(1);

        $this->assertEquals(
            'fsd587fds78dfsfdsa687dsa',
            $token->getToken()
        );

        $this->assertEquals(
            'testebizmarts',
            $token->getVendorname()
        );
    }

    public function testIsOwnedByCustomer()
    {
        $this->assertEquals(
            false,
            $this->tokenModel->isOwnedByCustomer(1)
        );
    }

    public function testIsCustomerUsingMaxTokenSlots()
    {
        $this->resourceMock->expects($this->once())
            ->method('getCustomerTokens')
            ->will($this->returnValue([]));

        $this->assertEquals(
            false,
            $this->tokenModel->isCustomerUsingMaxTokenSlots(1, 'testebizmarts')
        );
    }
}
