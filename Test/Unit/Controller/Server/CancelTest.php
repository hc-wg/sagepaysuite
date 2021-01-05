<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Ebizmarts\SagePaySuite\Controller\Server\Cancel;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\ObjectLoader\OrderLoader;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class CancelTest extends \PHPUnit_Framework_TestCase
{
    const QUOTE_ID = 1234;
    const RESERVED_ORDER_ID = 5678;

    /** @var Cart|\PHPUnit_Framework_MockObject_MockObject */
    private $cart;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    private $checkoutSession;

    /** @var Config|\PHPUnit_Framework_MockObject_MockObject */
    private $config;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    private $context;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $messageManager;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $om;

    /** @var Order|\PHPUnit_Framework_MockObject_MockObject */
    private $order;

    /** @var OrderFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $orderFactory;

    /** @var Quote|\PHPUnit_Framework_MockObject_MockObject */
    private $quote;

    /** @var QuoteIdMaskFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteIdMaskFactory;

    /** @var HttpRequest|\PHPUnit_Framework_MockObject_MockObject */
    private $request;

    /** @var HttpResponse|\PHPUnit_Framework_MockObject_MockObject */
    private $response;

    /** @var Cancel */
    private $serverCancelController;

    /** @var Logger|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteLogger;

    /** @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $urlBuilder;

    /** @var EncryptorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $encryptorMock;

    /** @var RecoverCart */
    private $recoverCartMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->cart = $this->getMockBuilder(Cart::class)->disableOriginalConstructor()->getMock();
        $this->checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $this->context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();
        $this->om = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $this->order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(HttpRequest::class)->disableOriginalConstructor()->getMock();
        $this->response = $this->getMockBuilder(HttpResponse::class)->disableOriginalConstructor()->getMock();
        $this->suiteLogger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)->disableOriginalConstructor()->getMock();

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->quoteIdMaskFactory = $this->getMockBuilder(QuoteIdMaskFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->recoverCartMock = $this
            ->getMockBuilder(RecoverCart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->atLeastOnce())->method('getRequest')->willReturn($this->request);
        $this->context->expects($this->atLeastOnce())->method('getResponse')->willReturn($this->response);
        $this->context->expects($this->atLeastOnce())->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->expects($this->atLeastOnce())->method('getUrl')->willReturn($this->urlBuilder);
        $this->context->expects($this->atLeastOnce())->method("getObjectManager")->willReturn($this->om);

        $this->serverCancelController = new Cancel(
            $this->context,
            $this->suiteLogger,
            $this->config,
            $this->logger,
            $this->checkoutSession,
            $this->quote,
            $this->quoteIdMaskFactory,
            $this->orderFactory,
            $this->encryptorMock,
            $this->recoverCartMock
        );
    }
    // @codingStandardsIgnoreEnd

    public function testExecute()
    {
        $storeId = 1;
        $quoteId = 69;
        $encrypted = '0:2:Dwn8kCUk6nZU5B7b0Xn26uYQDeLUKBrD:S72utt9n585GrslZpDp+DRpW+8dpqiu/EiCHXwfEhS0=';
        $encryptedOrderId = '0:3:Lq/5e1tdLdR19OaUuu1JTxD+7secLH91mWTNsT9c';
        $orderId = 44;

        $this->messageManager->expects($this->once())
            ->method('addError')->willReturn($this->messageManager);

        $this->request->expects($this->exactly(4))
            ->method('getParam')
            ->withConsecutive(['message'], ['_store'], ['quote'], ['orderId'])
            ->willReturnOnConsecutiveCalls('Some message', $storeId, $encrypted, $encryptedOrderId);

        $this->encryptorMock
            ->expects($this->exactly(2))
            ->method('decrypt')
            ->withConsecutive([$encrypted], [$encryptedOrderId])
            ->willReturnOnConsecutiveCalls($quoteId, $orderId);

        $this->quote->expects($this->once())->method("getId")->willReturn($quoteId);//self::QUOTE_ID
        $this->quote->expects($this->once())->method("load")->with($quoteId)->willReturnSelf();//self::QUOTE_ID

        $this->recoverCartMock
            ->expects($this->once())
            ->method('setShouldCancelOrder')
            ->with(true)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('setOrderId')
            ->with($orderId)
            ->willReturnSelf();
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
        $this->response->expects($this->once())
            ->method('setBody')
            ->with($body);
    }
}
