<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Token;

use Ebizmarts\SagePaySuite\Controller\Token\Delete as TokenDeleteController;
use Ebizmarts\SagePaySuite\Model\Logger\Logger as SuiteLogger;
use Ebizmarts\SagePaySuite\Model\Token as TokenModel;
use Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class DeleteTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenDeleteController */
    private $deleteTokenController;

    /** @var TokenModel|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenModelMock;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var VaultDetailsHandler|\PHPUnit_Framework_MockObject_MockObject */
    private $vaultDetailsHandlerMock;

    /** @var SuiteLogger|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteLoggerMock;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    private $customerSessionMock;

    /** @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $requestMock;

    /** @var ResultFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $resultFactoryMock;

    private $tokenId = 5;
    private $customerId = 34;

    protected function setUp()
    {
        $this->tokenModelMock = $this
            ->getMockBuilder(TokenModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock = $this
            ->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->vaultDetailsHandlerMock = $this
            ->getMockBuilder(VaultDetailsHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->suiteLoggerMock = $this
            ->getMockBuilder(SuiteLogger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this
            ->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this
            ->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deleteTokenController = $this
            ->getMockBuilder(TokenDeleteController::class)
            ->setMethods([
                'getRequest',
                'getResultFactory',
                '_redirect',
                'addSuccessMessage'
            ])
            ->setConstructorArgs([
                'context'             => $this->contextMock,
                'suiteLogger'         => $this->suiteLoggerMock,
                'logger'              => $this->loggerMock,
                'tokenModel'          => $this->tokenModelMock,
                'customerSession'     => $this->customerSessionMock,
                'vaultDetailsHandler' => $this->vaultDetailsHandlerMock
            ])
            ->getMock();
    }

    public function testExecuteCheckoutServer()
    {
        $this->deleteTokenController
            ->expects($this->exactly(4))
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->requestMock
            ->expects($this->exactly(4))
            ->method('getParam')
            ->withConsecutive(['token_id'], ['token_id'], ['checkout'], ['pmethod'])
            ->willReturnOnConsecutiveCalls($this->tokenId, $this->tokenId, '1', 'sagepaysuiteserver');

        $this->executeDeleteTokenForServer();

        $this->expectResultJson([
            "success" => true,
            'response' => true
        ]);

        $this->deleteTokenController->execute();
    }

    public function testExecuteCheckoutPI()
    {
        $this->deleteTokenController
            ->expects($this->exactly(4))
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->requestMock
            ->expects($this->exactly(4))
            ->method('getParam')
            ->withConsecutive(['token_id'], ['token_id'], ['checkout'], ['pmethod'])
            ->willReturnOnConsecutiveCalls($this->tokenId, $this->tokenId, '1', 'sagepaysuitepi');

        $this->executeDeleteTokenForPI();

        $this->expectResultJson([
            "success" => true,
            'response' => true
        ]);

        $this->deleteTokenController->execute();
    }

    public function testExecuteCustomerAccount()
    {
        $this->deleteTokenController
            ->expects($this->exactly(4))
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->requestMock
            ->expects($this->exactly(4))
            ->method('getParam')
            ->withConsecutive(['token_id'], ['token_id'], ['checkout'], ['isv'])
            ->willReturnOnConsecutiveCalls($this->tokenId, $this->tokenId, '', 'true');

        $this->executeDeleteTokenForPI();

        $this->expectResultJsonCustomerArea();

        $this->assertEquals(
            $this->deleteTokenController->execute(),
            true
        );
    }

    /**
     * @param $result
     */
    private function expectResultJson($result)
    {
        $resultJson = $this
            ->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->deleteTokenController
            ->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($resultJson);
        $resultJson
            ->expects($this->once())
            ->method('setData')
            ->with($result);
    }

    private function expectResultJsonCustomerArea()
    {
        $this->deleteTokenController
            ->expects($this->once())
            ->method('addSuccessMessage');
        $this->deleteTokenController
            ->expects($this->once())
            ->method('_redirect')
            ->with('sagepaysuite/customer/tokens');
    }

    private function executeDeleteTokenForServer()
    {
        $this->tokenModelMock
            ->expects($this->once())
            ->method('loadToken')
            ->willReturnSelf();
        $this->customerSessionMock
            ->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($this->customerId);
        $this->tokenModelMock
            ->expects($this->once())
            ->method('isOwnedByCustomer')
            ->willReturn(true);
        $this->tokenModelMock
            ->expects($this->once())
            ->method('deleteToken');
    }

    private function executeDeleteTokenForPI()
    {
        $this->customerSessionMock
            ->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($this->customerId);
        $this->vaultDetailsHandlerMock
            ->expects($this->once())
            ->method('deleteToken')
            ->with($this->tokenId, $this->customerId)
            ->willReturn(true);
    }
}
