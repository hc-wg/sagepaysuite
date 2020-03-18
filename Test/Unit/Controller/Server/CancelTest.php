<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Ebizmarts\SagePaySuite\Helper\RepositoryQuery;
use Klarna\Core\Model\OrderRepository;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Message\ManagerInterface;
use Ebizmarts\SagePaySuite\Controller\Server\Cancel;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Ebizmarts\SagePaySuite\Model\RecoverCart;

use Magento\Framework\Encryption\EncryptorInterface;

class CancelTest extends \PHPUnit\Framework\TestCase
{
    const QUOTE_ID = 1234;
    const RESERVED_ORDER_ID = 5678;

    /** @var Cart|\PHPUnit_Framework_MockObject_MockObject */
    private $cart;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    private $checkoutSessionMock;

    /** @var Config|\PHPUnit_Framework_MockObject_MockObject */
    private $config;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $messageManagerMock;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $om;

    /** @var Order|\PHPUnit_Framework_MockObject_MockObject */
    private $orderMock;

    /** @var OrderRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $orderRepositoryMock;

    /** @var Quote|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteMock;

    /** @var QuoteIdMaskFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteIdMaskFactory;

    /** @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject */
    private $requestMock;

    /** @var HttpResponse|\PHPUnit_Framework_MockObject_MockObject */
    private $responseMock;

    /** @var Cancel */
    private $serverCancelController;

    /** @var Logger|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteLoggerMock;

    /** @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $urlBuilderMock;

    /** @var EncryptorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $encryptorMock;

    /** @var RecoverCart */
    private $recoverCartMock;

    /** @var RepositoryQuery */
    private $repositoryQueryMock;

    /** @var SearchCriteria */
    private $searchCriteriaMock;

    /** @var SearchResults|\PHPUnit_Framework_MockObject_MockObject */
    private $searchResultsMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->setMethods([
                'saveErrorMessage', 'getRequest', 'getResponse', 'getMessageManager',
                'getUrl', 'getObjectManager', 'inactivateQuote'
            ])
            ->disableOriginalConstructor()->getMock();
        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();
        $this->om = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['load', 'getId', 'getReservedOrderId', 'setStoreId', 'setIsactive', 'save'])
            ->disableOriginalConstructor()->getMock();
        $this->requestMock = $this->getMockBuilder(HttpRequest::class)->disableOriginalConstructor()->getMock();
        $this->responseMock = $this->getMockBuilder(HttpResponse::class)->disableOriginalConstructor()->getMock();
        $this->suiteLoggerMock = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)->disableOriginalConstructor()->getMock();
        $this->repositoryQueryMock = $this->getMockBuilder(RepositoryQuery::class)
            ->setMethods(['buildSearchCriteriaWithOR'])
            ->disableOriginalConstructor()->getMock();

        $this->orderRepositoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList'])
            ->getMock();

        $this->quoteIdMaskFactory = $this->getMockBuilder(QuoteIdMaskFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->recoverCartMock = $this
            ->getMockBuilder(RecoverCart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaMock = $this
            ->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchResultsMock = $this->getMockBuilder(SearchResults::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTotalCount', 'getItems'])
            ->getMock();

        $this->contextMock->expects($this->atLeastOnce())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects($this->atLeastOnce())->method('getResponse')->willReturn($this->responseMock);
        $this->contextMock->expects($this->atLeastOnce())->method('getMessageManager')->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->atLeastOnce())->method('getUrl')->willReturn($this->urlBuilderMock);
        $this->contextMock->expects($this->atLeastOnce())->method("getObjectManager")->willReturn($this->om);

        $this->serverCancelController = new Cancel(
            $this->contextMock,
            $this->suiteLoggerMock,
            $this->config,
            $this->logger,
            $this->checkoutSessionMock,
            $this->quoteMock,
            $this->quoteIdMaskFactory,
            $this->orderRepositoryMock,
            $this->encryptorMock,
            $this->recoverCartMock,
            $this->repositoryQueryMock
        );
    }
    // @codingStandardsIgnoreEnd

    public function testExecute()
    {
        $storeId = 1;
        $quoteId = 69;
        $encrypted = '0:2:Dwn8kCUk6nZU5B7b0Xn26uYQDeLUKBrD:S72utt9n585GrslZpDp+DRpW+8dpqiu/EiCHXwfEhS0=';

        //$this->contextMock->expects($this->atLeastOnce())->method("saveErrorMessage");
        $this->messageManagerMock->expects($this->once())
            ->method('addError')->willReturn($this->messageManagerMock);

        $this->requestMock->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(['message'], ['_store'], ['quote'])
            ->willReturnOnConsecutiveCalls('Some message', $storeId, $encrypted);

        $this->encryptorMock->expects($this->once())->method('decrypt')
            ->with($encrypted)
            ->willReturn($quoteId);

        $this->quoteMock->expects($this->once())->method("setStoreId")
            ->with($storeId)
            ->willReturnSelf();//self::QUOTE_ID

        $this->quoteMock->expects($this->once())->method("load")
            ->with($quoteId)
            ->willReturnSelf();//self::QUOTE_ID

        $this->quoteMock->expects($this->once())->method("getId")
            ->willReturn($quoteId);//self::QUOTE_ID

        $this->quoteMock->expects($this->once())->method("getReservedOrderId")->willReturn(self::RESERVED_ORDER_ID);

        $filter = array(
            'field' => 'increment_id',
            'value' => self::RESERVED_ORDER_ID,
            'conditionType' => 'eq'
        );

        $this->repositoryQueryMock->expects($this->once())->method("buildSearchCriteriaWithOR")
            ->with(array($filter), 1, 1)
            ->willReturn($this->searchCriteriaMock);

        $this->orderRepositoryMock->expects($this->once())->method("getList")
            ->with($this->searchCriteriaMock)->willReturn($this->searchResultsMock);

        $this->searchResultsMock->expects($this->once())->method('getTotalCount')
            ->willReturn(1);

        $this->searchResultsMock->expects($this->once())->method('getItems')
            ->willReturn(array($this->orderMock));

        $this->orderMock->expects($this->once())->method("getId")
            ->willReturn(self::RESERVED_ORDER_ID);

        $this->recoverCartMock
            ->expects($this->once())
            ->method('setShouldCancelOrder')
            ->with(true)
            ->willReturnSelf();

        $this->quoteMock->expects($this->once())->method('setIsactive');
        $this->quoteMock->expects($this->once())->method('save');

        $this->recoverCartMock
            ->expects($this->once())
            ->method('execute');

        $this->expectSetBody(
            '<script>window.top.location.href = "'
            . '";</script>'
        );

        $this->serverCancelController->execute();
    }

    /**
     * @param $body
     */
    private function expectSetBody($body)
    {
        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($body);
    }
}
