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
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\Manager;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
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

    /** @var SearchResults|\PHPUnit_Framework_MockObject_MockObject */
    private $searchResultsMock;

    public function setUp()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->suiteLoggerMock = $this->getMockBuilder(SuiteLogger::class)->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();

        $this->requestMock = $this->getMockBuilder(HttpRequest::class)->disableOriginalConstructor()->getMock();
        $this->responseMock = $this->getMockBuilder(HttpResponse::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->messageManagerMock = $this->getMockBuilder(Manager::class)
            ->setMethods(['addError'])
            ->disableOriginalConstructor()->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getReservedOrderId'])
            ->disableOriginalConstructor()->getMock();

        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->repositoryQueryMock = $this->getMockBuilder(RepositoryQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['buildSearchCriteriaWithOR'])
            ->getMock();

        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList'])
            ->getMock();

        $this->quoteRepositoryMock = $this->getMockBuilder(QuoteRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->searchResultsMock = $this->getMockBuilder(SearchResults::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTotalCount', 'getItems'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->objectManagerHelper->getObject(
            Success::class,
            [
                '_checkoutSession' => $this->checkoutSessionMock,
                '_suiteLogger' => $this->suiteLoggerMock,
                'encryptor' => $this->encryptorMock,
                '_quoteRepository' => $this->quoteRepositoryMock,
                '_orderRepository' => $this->orderRepositoryMock,
                '_repositoryQuery' => $this->repositoryQueryMock,
                'messageManager' => $this->messageManagerMock
            ]
        );
    }

    public function testExecute()
    {
        $storeId = 1;
        $quoteId = 69;
        $encrypted = '0:2:Dwn8kCUk6nZU5B7b0Xn26uYQDeLUKBrD:S72utt9n585GrslZpDp+DRpW+8dpqiu/EiCHXwfEhS0=';
        $reserverdOrderId = 1;

        $this->contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())->method('getResponse')->willReturn($this->responseMock);
        $this->contextMock->expects($this->any())->method('getUrl')->willReturn($this->urlBuilderMock);

        $this->requestMock->expects($this->exactly(2))->method('getParam')
            ->withConsecutive(['_store'], ['quoteid'])
            ->willReturnOnConsecutiveCalls($storeId, $encrypted);

        $this->encryptorMock->expects($this->once())->method('decrypt')
            ->with($encrypted)
            ->willReturn($quoteId);

        $this->quoteRepositoryMock->expects($this->once())->method('get')
            ->with($quoteId, [$storeId])
            ->willReturn($this->quoteMock);

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock->expects($this->once())->method('getReservedOrderId')
            ->willReturn($reserverdOrderId);

        $filter = array(
            'field' => 'increment_id',
            'value' => $reserverdOrderId,
            'conditionType' => 'eq'
        );
        $this->repositoryQueryMock->expects($this->once())->method('buildSearchCriteriaWithOR')
            ->with(array($filter))->willReturn($searchCriteriaMock);

        $this->orderRepositoryMock->expects($this->once())->method('getList')
            ->with($searchCriteriaMock)->willReturn($this->searchResultsMock);

        $this->searchResultsMock->expects($this->once())->method('getTotalCount')
            ->willReturn(1);

        $this->searchResultsMock->expects($this->once())->method('getItems')
            ->willReturn(array($this->orderMock));

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

        $this->serverSuccessController->execute();
    }

    public function testException()
    {
        $storeId = 1;
        $quoteId = 69;
        $encrypted = '0:2:Dwn8kCUk6nZU5B7b0Xn26uYQDeLUKBrD:S72utt9n585GrslZpDp+DRpW+8dpqiu/EiCHXwfEhS0=';

        $this->contextMock->expects($this->once())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())->method('getResponse')->willReturn($this->responseMock);
        $this->contextMock->expects($this->any())->method('getUrl')->willReturn($this->urlBuilderMock);
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

        $this->serverSuccessController->execute();
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
