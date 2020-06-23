<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Ebizmarts\SagePaySuite\Controller\Server\Success;
use Ebizmarts\SagePaySuite\Model\Logger\Logger as SuiteLogger;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;

class SuccessTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    private $checkoutSessionMock;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var Logger|\PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    /** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
    private $messageManagerMock;

    /** @var Order|\PHPUnit_Framework_MockObject_MockObject */
    private $orderMock;

    /** @var OrderFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $orderFactoryMock;

    /** @var Quote|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteMock;

    /** @var QuoteFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteFactory;

    /** @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject */
    private $requestMock;

    /** @var Success */
    private $serverSuccessController;

    /** @var SuiteLogger|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteLoggerMock;

    /** @var EncryptorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $encryptorMock;

    /** @var RedirectFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $resultRedirectFactoryMock;

    /** @var Redirect */
    private $resultRedirectMock;

    public function setUp()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->suiteLoggerMock = $this->getMockBuilder(SuiteLogger::class)->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();

        $this->requestMock = $this->getMockBuilder(HttpRequest::class)->disableOriginalConstructor()->getMock();
        $this->messageManagerMock = $this->getMockBuilder(Manager::class)->setMethods(['addError'])->disableOriginalConstructor()->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)->disableOriginalConstructor()->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->orderFactoryMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->resultRedirectFactoryMock = $this
            ->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->resultRedirectMock = $this
            ->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMock();
    }

    public function testExecute()
    {
        $storeId = 1;
        $quoteId = 69;
        $encrypted = '0:2:Dwn8kCUk6nZU5B7b0Xn26uYQDeLUKBrD:S72utt9n585GrslZpDp+DRpW+8dpqiu/EiCHXwfEhS0=';

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->requestMock->expects($this->exactly(2))->method('getParam')
            ->withConsecutive(['_store'], ['quoteid'])
            ->willReturnOnConsecutiveCalls($storeId, $encrypted);

        $this->encryptorMock->expects($this->once())->method('decrypt')
            ->with($encrypted)
            ->willReturn($quoteId);

        $this->quoteFactory->expects($this->once())->method('create')->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->once())->method('setStoreId')->with($storeId);

        $this->quoteMock->expects($this->once())->method('load')->willReturnSelf();

        $this->orderMock->expects($this->once())->method('loadByIncrementId')->willReturnSelf();

        $this->orderFactoryMock->expects($this->once())->method('create')->willReturn($this->orderMock);

        $this->contextMock
            ->expects($this->once())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);

        $this->resultRedirectFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirectMock);

        $this->resultRedirectMock
            ->expects($this->once())
            ->method('setPath')
            ->with('checkout/onepage/success', ['_secure' => true]);

        $this->serverSuccessController = new Success(
            $this->contextMock,
            $this->suiteLoggerMock,
            $this->loggerMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->quoteFactory,
            $this->encryptorMock
        );

        $this->serverSuccessController->execute();
    }

    public function testException()
    {
        $storeId = 1;
        $quoteId = 69;
        $encrypted = '0:2:Dwn8kCUk6nZU5B7b0Xn26uYQDeLUKBrD:S72utt9n585GrslZpDp+DRpW+8dpqiu/EiCHXwfEhS0=';

        $this->contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())->method('getMessageManager')->willReturn($this->messageManagerMock);

        $this->requestMock->expects($this->exactly(2))->method('getParam')
            ->withConsecutive(['_store'], ['quoteid'])
            ->willReturnOnConsecutiveCalls($storeId, $encrypted);

        $this->encryptorMock->expects($this->once())->method('decrypt')
            ->with($encrypted)
            ->willReturn($quoteId);
            
        $expectedException = new \Exception("Could not load quote.");

        $this->quoteFactory->expects($this->once())->method('create')->willThrowException($expectedException);

        $this->loggerMock->expects($this->once())->method('critical')->with($expectedException);
        $this->messageManagerMock->expects($this->once())->method('addError')->with('An error ocurred.');

        $this->contextMock
            ->expects($this->once())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);

        $this->resultRedirectFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirectMock);

        $this->resultRedirectMock
            ->expects($this->once())
            ->method('setPath')
            ->with('checkout/onepage/success', ['_secure' => true]);

        $this->serverSuccessController = new Success(
            $this->contextMock,
            $this->suiteLoggerMock,
            $this->loggerMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->quoteFactory,
            $this->encryptorMock
        );

        $this->serverSuccessController->execute();
    }
}
