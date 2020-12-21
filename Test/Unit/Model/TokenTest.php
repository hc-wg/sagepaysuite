<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Plugin\DeleteTokenFromSagePay;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    private $objectManagerHelper;
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

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $configMock;

    /**
     * @var DeleteTokenFromSagePay|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deleteTokenFromSagePayMock;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $suiteLoggerMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->resourceMock = $this
            ->getMockBuilder('Magento\Framework\Model\ResourceModel\Db\AbstractDb')
            ->setMethods(["getIdFieldName", "_construct", "getConnection", "save",
                "getCustomerTokens", "getTokenById", "isTokenOwnedByCustomer"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this
            ->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->postApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Post')
            ->disableOriginalConstructor()
            ->getMock();

        $this->deleteTokenFromSagePayMock = $this
            ->getMockBuilder(DeleteTokenFromSagePay::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token',
            [
                'suiteLogger' => $this->suiteLoggerMock,
                'resource' => $this->resourceMock,
                'config'   => $this->configMock,
                'postApi'  => $this->postApiMock,
                'deleteTokenFromSagePay' => $this->deleteTokenFromSagePayMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testSaveTokenNoCustomerId()
    {
        $token = $this->tokenModel->saveToken(
            null,
            'fsd587fds78dfsfdsa687dsa',
            'VISA',
            '0006',
            '02',
            '22',
            'testebizmarts'
        );

        $this->assertEmpty(
            $token->getToken()
        );

        $this->assertEmpty(
            $token->getVendorname()
        );
    }

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

    public function testGetCustomerTokensEmpty()
    {
        $this->assertEquals(
            [],
            $this->tokenModel->getCustomerTokens(null, 'testebizmarts')
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage Unable to delete token from Opayo: missing data to proceed
     */
    public function testDeleteTokenException()
    {
        $exception = new NoSuchEntityException(__('Unable to delete token from Opayo: missing data to proceed'));

        $token = $this->tokenModel->saveToken(
            1,
            'fsd587fds78dfsfdsa687dsa',
            'VISA',
            '0006',
            '02',
            '22',
            'testebizmarts'
        );

        $this->deleteTokenFromSagePayMock
            ->expects($this->once())
            ->method('deleteFromSagePay')
            ->with('fsd587fds78dfsfdsa687dsa')
            ->willThrowException($exception);

        $this->tokenModel->deleteToken();
    }

    /**
     *
     */
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

        $this->deleteTokenFromSagePayMock
            ->expects($this->once())
            ->method('deleteFromSagePay')
            ->with('fsd587fds78dfsfdsa687dsa');

        $token->deleteToken();
    }

    public function testDeleteTokenDelete()
    {
        $token = 'fsd587fds78dfsfdsa687dsa';

        $tokenModelMock = $this->buildTokenModelMock();
        $tokenModelMock->expects($this->once())->method('getToken')->willReturn($token);

        $this->deleteTokenFromSagePayMock
            ->expects($this->once())
            ->method('deleteFromSagePay')
            ->with($token);

        $tokenModelMock->expects($this->once())->method('getId')->willReturn(456);
        $tokenModelMock->expects($this->once())->method('delete');

        $tokenModelMock->deleteToken();
    }

    public function testLoadToken1()
    {
        $this->resourceMock->expects($this->any())
            ->method('getTokenById')
            ->willReturn(null);

        $token = $this->tokenModel->loadToken(1);

        $this->assertNull($token);
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

    public function testIsOwnedByCustomer1()
    {
        $resourceMock = $this->getMockBuilder(\Magento\Framework\Model\ResourceModel\Db\AbstractDb::class)
            ->setMethods(['isTokenOwnedByCustomer', '_construct'])
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock->expects($this->once())->method('isTokenOwnedByCustomer')->with(121, 456)->willReturn(true);

        /** @var \Ebizmarts\SagePaySuite\Model\Token|\PHPUnit_Framework_MockObject_MockObject $tokenModelMock */
        $tokenModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Token::class)
            ->setMethods(['getResource', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $tokenModelMock->expects($this->once())->method('getResource')->willReturn($resourceMock);

        $tokenModelMock->expects($this->exactly(2))->method('getId')->willReturn(456);

        $tokenModelMock->isOwnedByCustomer(121);
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
        $usingMaxToken = true;

        if ($this->configMock->getMaxTokenPerCustomer() > 1) {
            $usingMaxToken = false;
        }

        $this->resourceMock->expects($this->once())
            ->method('getCustomerTokens')
            ->will($this->returnValue([]));

        $this->assertEquals(
            $usingMaxToken,
            $this->tokenModel->isCustomerUsingMaxTokenSlots(1, 'testebizmarts')
        );
    }

    public function testIsCustomerUsingMaxTokenSlots1()
    {
        $this->assertEquals(
            true,
            $this->tokenModel->isCustomerUsingMaxTokenSlots(null, 'testebizmarts')
        );
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Model\Token
     */
    private function buildTokenModelMock()
    {
        $contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $registryMock = $this
            ->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock = $this
            ->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceCollectionMock = $this
            ->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \Ebizmarts\SagePaySuite\Model\Token $tokenModelMock */
        $tokenModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Token::class)
            ->setMethods(['getId', 'delete', 'getToken', '_construct'])
            ->setConstructorArgs(
                [
                    $contextMock,
                    $registryMock,
                    $this->suiteLoggerMock,
                    $this->postApiMock,
                    $this->configMock,
                    $this->deleteTokenFromSagePayMock,
                    $resourceMock,
                    $resourceCollectionMock
                ]
            )
            ->getMock();

        return $tokenModelMock;
    }
}
