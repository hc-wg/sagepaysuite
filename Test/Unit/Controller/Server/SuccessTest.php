<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Ebizmarts\SagePaySuite\Controller\Server\Success;
use Ebizmarts\SagePaySuite\Helper\RepositoryQuery;
use Ebizmarts\SagePaySuite\Model\Logger\Logger as SuiteLogger;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface as Logger;

class SuccessTest extends \PHPUnit_Framework_TestCase
{
    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    private $checkoutSessionMock;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var Logger|\PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    /** @var MessageManager|\PHPUnit_Framework_MockObject_MockObject */
    private $messageManagerMock;

    /** @var Order|\PHPUnit_Framework_MockObject_MockObject */
    private $orderMock;

    /** @var OrderRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $orderRepositoryMock;

    /** @var Quote|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteMock;

    /** @var QuoteRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteRepositoryMock;

    /** @var RepositoryQuery|\PHPUnit_Framework_MockObject_MockObject */
    private $repositoryQueryMock;

    /** @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject */
    private $requestMock;

    /** @var HttpResponse|\PHPUnit_Framework_MockObject_MockObject */
    private $responseMock;

    /** @var Success */
    private $serverSuccessController;

    /** @var SuiteLogger|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteLoggerMock;

    /** @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $urlBuilderMock;

    /** @var EncryptorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $encryptorMock;

    /** @var ObjectManagerHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $objectManagerHelper;

    public function setUp()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->suiteLoggerMock = $this->getMockBuilder(SuiteLogger::class)->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();

        $this->requestMock = $this->getMockBuilder(HttpRequest::class)->disableOriginalConstructor()->getMock();
        $this->responseMock = $this->getMockBuilder(HttpResponse::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->messageManager = $this->getMockBuilder(MessageManager::class)->disableOriginalConstructor()->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)->disableOriginalConstructor()->getMock();
        $this->messageManagerMock = $this->getMockBuilder(MessageManager::class)->disableOriginalConstructor()->getMock();

        $this->repositoryQueryMock = $this->getMockBuilder(RepositoryQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['buildSearchCriteriaWithOR'])
            ->getMock();

        $this->quoteRepositoryMock = $this->getMockBuilder(QuoteRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->objectManagerHelper->getObject(
            Success::class,
            [
                '_checkoutSession' => $this->checkoutSessionMock,
                '_suiteLogger' => $this->suiteLoggerMock,
                /*'_formModel' => $this->formModelMock,
                'orderSender' => $this->orderSenderMock,
                'updateOrderCallback' => $this->updateOrderCallbackMock,
                'suiteHelper' => $this->suiteHelperMock,*/
                'encryptor' => $this->encryptorMock,
                '_quoteRepository' => $this->quoteRepositoryMock,
                '_orderRepository' => $this->orderRepositoryMock,
                '_repositoryQuery' => $this->repositoryQueryMock
            ]
        );
    }

    public function testExecute()
    {
        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())->method('getResponse')->willReturn($this->responseMock);
        $this->contextMock->expects($this->any())->method('getUrl')->willReturn($this->urlBuilderMock);

        $this->quoteRepositoryMock->expects($this->once())->method('get')->with(1)->willReturn($this->quoteMock);

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repositoryQueryMock->expects($this->once())->method('buildSearchCriteriaWithOR')
            ->with(array($searchCriteriaMock))->willReturn($searchCriteriaMock);

        $this->orderRepositoryMock->expects($this->once())->method('getList')
            ->with($searchCriteriaMock)->willReturn(array($this->orderMock));

        $this->_expectSetBody(
            '<script>window.top.location.href = "'
            . $this->urlBuilderMock->getUrl('checkout/onepage/success', ['_secure' => true])
            . '";</script>'
        );

        $this->serverSuccessController = new Success(
            $this->contextMock,
            $this->suiteLoggerMock,
            $this->loggerMock,
            $this->checkoutSessionMock,
            $this->orderRepositoryMock,
            $this->quoteRepositoryMock,
            $this->encryptorMock,
            $this->repositoryQueryMock
        );

        //$this->serverSuccessController->execute();
        $this->objectManagerHelper->execute();
    }

    public function testException()
    {
        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())->method('getResponse')->willReturn($this->responseMock);
        $this->contextMock->expects($this->any())->method('getMessageManager')->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())->method('getUrl')->willReturn($this->urlBuilderMock);

        $expectedException = new \Exception("Could not load quote.");
        $this->quoteRepositoryMock->expects($this->once())->method('get')->willThrowException($expectedException);
        $this->loggerMock->expects($this->once())->method('critical')->with($expectedException);
        $this->messageManagerMock->expects($this->once())->method('addError')->with('An error ocurred.');

        $this->serverSuccessController = new Success(
            $this->contextMock,
            $this->suiteLoggerMock,
            $this->loggerMock,
            $this->checkoutSessionMock,
            $this->orderRepositoryMock,
            $this->quoteRepositoryMock,
            $this->encryptorMock,
            $this->repositoryQueryMock
        );

//        $this->serverSuccessController->execute();
        $this->objectManagerHelper->execute();
    }

    /**
     * @param $body
     */
    private function _expectSetBody($body)
    {
        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($body);
    }
}
