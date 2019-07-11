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
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface as Logger;

class SuccessTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    private $checkoutSession;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    private $context;

    /** @var Logger|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var MessageManager|\PHPUnit_Framework_MockObject_MockObject */
    private $messageManager;

    /** @var Order|\PHPUnit_Framework_MockObject_MockObject */
    private $order;

    /** @var OrderFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $orderFactory;

    /** @var Quote|\PHPUnit_Framework_MockObject_MockObject */
    private $quote;

    /** @var QuoteFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteFactory;

    /** @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject */
    private $request;

    /** @var HttpResponse|\PHPUnit_Framework_MockObject_MockObject */
    private $response;

    /** @var Success */
    private $serverSuccessController;

    /** @var SuiteLogger|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteLogger;

    /** @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $urlBuilder;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->suiteLogger = $this->getMockBuilder(SuiteLogger::class)->disableOriginalConstructor()->getMock();
        $this->logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $this->checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(HttpRequest::class)->disableOriginalConstructor()->getMock();
        $this->response = $this->getMockBuilder(HttpResponse::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->messageManager = $this->getMockBuilder(MessageManager::class)->disableOriginalConstructor()->getMock();

        $this->order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();

        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)->disableOriginalConstructor()->getMock();

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
    }

    public function testExecute()
    {
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->request);
        $this->context->expects($this->any())->method('getResponse')->willReturn($this->response);
        $this->context->expects($this->any())->method('getUrl')->willReturn($this->urlBuilder);

        $this->quote->expects($this->once())->method('load')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($this->quote);

        $this->order->expects($this->once())->method('loadByIncrementId')->willReturnSelf();
        $this->orderFactory->expects($this->once())->method('create')->willReturn($this->order);

        $this->_expectSetBody(
            '<script>window.top.location.href = "'
            . $this->urlBuilder->getUrl('checkout/onepage/success', ['_secure' => true])
            . '";</script>'
        );

        $this->serverSuccessController = new Success(
            $this->context,
            $this->suiteLogger,
            $this->logger,
            $this->checkoutSession,
            $this->orderFactory,
            $this->quoteFactory,
            $this->encryptorMock
        );

        $this->serverSuccessController->execute();
    }

    public function testException()
    {
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->request);
        $this->context->expects($this->any())->method('getResponse')->willReturn($this->response);
        $this->context->expects($this->any())->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->expects($this->any())->method('getUrl')->willReturn($this->urlBuilder);

        $expectedException = new \Exception("Could not load quote.");
        $this->quoteFactory->expects($this->once())->method('create')->willThrowException($expectedException);
        $this->logger->expects($this->once())->method('critical')->with($expectedException);

        $this->serverSuccessController = new Success(
            $this->context,
            $this->suiteLogger,
            $this->logger,
            $this->checkoutSession,
            $this->orderFactory,
            $this->quoteFactory,
            $this->encryptorMock
        );

        $this->serverSuccessController->execute();
    }

    /**
     * @param $body
     */
    private function _expectSetBody($body)
    {
        $this->response->expects($this->once())
            ->method('setBody')
            ->with($body);
    }
}
