<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Ebizmarts\SagePaySuite\Controller\Server\Success;
use Ebizmarts\SagePaySuite\Model\Logger\Logger as SuiteLogger;
use Ebizmarts\SagePaySuite\Model\ObjectLoader\OrderLoader;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface as Logger;

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

    /** @var Quote|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteMock;

    /** @var QuoteRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteRepositoryMock;

    /** @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject */
    private $requestMock;

    /** @var Success */
    private $serverSuccessController;

    /** @var SuiteLogger|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteLoggerMock;

    /** @var EncryptorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $encryptorMock;

    /** @var OrderLoader */
    private $orderLoaderMock;

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

        $this->orderLoaderMock = $this
            ->getMockBuilder(OrderLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteRepositoryMock = $this->getMockBuilder(QuoteRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
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
        $orderId = 52;
        $incrementId = "10000014";
        $transactionId = "F8A9409B-9C6C-4CCC-74DE-A6ED131B1DCC";

        $this->contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);

        $this->requestMock->expects($this->exactly(2))->method('getParam')
            ->withConsecutive(['_store'], ['quoteid'])
            ->willReturnOnConsecutiveCalls($storeId, $encrypted);

        $this->encryptorMock->expects($this->once())->method('decrypt')
            ->with($encrypted)
            ->willReturn($quoteId);

        $this->quoteRepositoryMock->expects($this->once())->method('get')
            ->with($quoteId, [$storeId])
            ->willReturn($this->quoteMock);

        $this->orderLoaderMock
            ->expects($this->once())
            ->method('loadOrderFromQuote')
            ->with($this->quoteMock)
            ->willReturn($this->orderMock);

        $this->orderMock
            ->expects($this->once())
            ->method('getEntityId')
            ->willReturn($orderId);
        $this->orderMock
            ->expects($this->exactly(2))
            ->method('getIncrementId')
            ->willReturn($incrementId);
        $this->orderMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn("canceled");

        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock
            ->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);
        $paymentMock
            ->expects($this->once())
            ->method('getLastTransId')
            ->willReturn($transactionId);

        $this->resultRedirectFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirectMock);

        $this->resultRedirectMock
            ->expects($this->once())
            ->method('setPath')
            ->with('checkout/onepage/success', ['_secure' => true]);

        $this->suiteLoggerMock
            ->expects($this->once())
            ->method('orderEndLog')
            ->with($incrementId, $quoteId, $transactionId)
            ->willReturnSelf();

        $this->serverSuccessController = new Success(
            $this->contextMock,
            $this->suiteLoggerMock,
            $this->loggerMock,
            $this->checkoutSessionMock,
            $this->quoteRepositoryMock,
            $this->encryptorMock,
            $this->orderLoaderMock,
            $this->resultRedirectFactoryMock
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

        $this->quoteRepositoryMock->expects($this->once())->method('get')
            ->with($quoteId, [$storeId])
            ->willThrowException($expectedException);

        $expectedException = new \Exception("Could not load quote.");

        $this->loggerMock->expects($this->once())->method('critical')->with($expectedException);
        $this->messageManagerMock->expects($this->once())->method('addError')->with('An error ocurred.');

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
            $this->quoteRepositoryMock,
            $this->encryptorMock,
            $this->orderLoaderMock,
            $this->resultRedirectFactoryMock
        );

        $this->serverSuccessController->execute();
    }
}
