<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Message\ManagerInterface;

class RecoverCartTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ORDER_ID   = 7832;
    const TEST_QUOTE_ID   = 123;
    const TEST_STORE_ID   = 1;
    const TEST_PRODUCT_ID = 635;

    /** @var Order */
    private $orderMock;

    /** @var Quote */
    private $quoteMock;

    /** @var CheckoutSession */
    private $checkoutSessionMock;

    /** @var RecoverCart */
    private $recoverCart;

    /** @var OrderRepositoryInterface */
    private $orderRepositoryMock;

    /** @var CartRepositoryInterface */
    private $quoteRepositoryMock;

    /** @var QuoteFactory */
    private $quoteFactoryMock;

    /** @var Logger */
    private $suiteLoggerMock;

    /** @var DataObjectFactory */
    private $dataObjectFactoryMock;

    /** @var ManagerInterface */
    private $messageManagerMock;

    protected function setUp()
    {
        $this->checkoutSessionMock = $this
            ->getMockBuilder(CheckoutSession::class)
            ->setMethods(['setData', 'getData', 'getQuote', 'replaceQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this
            ->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteRepositoryMock = $this
            ->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteFactoryMock = $this
            ->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectFactoryMock = $this
            ->getMockBuilder(DataObjectFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManagerMock = $this
            ->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->recoverCart = $objectManagerHelper->getObject(
            '\Ebizmarts\SagePaySuite\Model\RecoverCart',
            [
                'checkoutSession'   => $this->checkoutSessionMock,
                'suiteLogger'       => $this->suiteLoggerMock,
                'orderRepository'   => $this->orderRepositoryMock,
                'quoteFactory'      => $this->quoteFactoryMock,
                'quoteRepository'   => $this->quoteRepositoryMock,
                'dataObjectFactory' => $this->dataObjectFactoryMock,
                'messageManager'    => $this->messageManagerMock
            ]
        );
    }

    public function testExecute()
    {
        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('getData')
            ->with(SagePaySession::PRESAVED_PENDING_ORDER_KEY)
            ->willReturn(self::TEST_ORDER_ID);

        $this->orderRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->orderMock);

        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->orderMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::TEST_ORDER_ID);
        $this->orderMock
            ->expects($this->once())
            ->method('getState')
            ->willReturn(Order::STATE_PENDING_PAYMENT);
        $this->orderMock
            ->expects($this->once())
            ->method('cancel')
            ->willReturnSelf();
        $this->orderMock
            ->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->orderMock
            ->expects($this->once())
            ->method('getQuoteId')
            ->willReturn(self::TEST_QUOTE_ID);

        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with(self::TEST_QUOTE_ID)
            ->willReturn($this->quoteMock);

        $itemMock = $this
            ->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock
            ->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$itemMock]);

        $newQuoteMock = $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($newQuoteMock);

        $newQuoteMock
            ->expects($this->once())
            ->method('setStoreId')
            ->with(self::TEST_STORE_ID)
            ->willReturnSelf();

        $this->quoteMock
            ->expects($this->once())
            ->method('getStoreId')
            ->willReturn(self::TEST_STORE_ID);

        $newQuoteMock
            ->expects($this->once())
            ->method('setIsActive')
            ->with(1);
        $newQuoteMock
            ->expects($this->once())
            ->method('setReservedOrderId')
            ->with(null);

        $productMock = $this
            ->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $productAbstractTypeMock = $this
            ->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock
            ->expects($this->once())
            ->method('getTypeInstance')
            ->with(true)
            ->willReturn($productAbstractTypeMock);

        $options = [
            'info_buyRequest' => [
                'uenc'    => 'aHR0cDovL20yMzMubG9jYWwv',
                'product' => 6,
                'qty'     => 1
            ]
        ];

        $info = [
            'uenc'    => 'aHR0cDovL20yMzMubG9jYWwv',
            'product' => 6,
            'qty'     => 1
        ];

        $productAbstractTypeMock
            ->expects($this->once())
            ->method('getOrderOptions')
            ->with($productMock)
            ->willReturn($options);

        $requestMock = $this
            ->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($requestMock);

        $requestMock
            ->expects($this->once())
            ->method('setData')
            ->with($info)
            ->willReturnSelf();

        $newQuoteMock
            ->expects($this->once())
            ->method('addProduct')
            ->with($productMock, $requestMock)
            ->willReturnSelf();
        $newQuoteMock
            ->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();
        $newQuoteMock
            ->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('replaceQuote')
            ->with($newQuoteMock);

        $this->checkoutSessionMock
            ->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                [SagePaySession::PRESAVED_PENDING_ORDER_KEY, null],
                [SagePaySession::CONVERTING_QUOTE_TO_ORDER, 0]
            );

        $this->recoverCart->setShouldCancelOrder(true)->execute();
    }

    public function testExecuteOrderNotAvailable()
    {
        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('getData')
            ->with(SagePaySession::PRESAVED_PENDING_ORDER_KEY)
            ->willReturn(null);

        $this->messageManagerMock
            ->expects($this->once())
            ->method('addError')
            ->with(RecoverCart::ORDER_ERROR_MESSAGE)
            ->willReturnSelf();

        $this->checkoutSessionMock
            ->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                [SagePaySession::PRESAVED_PENDING_ORDER_KEY, null],
                [SagePaySession::CONVERTING_QUOTE_TO_ORDER, 0]
            );

        $this->recoverCart->setShouldCancelOrder(true)->execute();
    }

    public function testExecuteQuoteNotAvailable()
    {
        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('getData')
            ->with(SagePaySession::PRESAVED_PENDING_ORDER_KEY)
            ->willReturn(self::TEST_ORDER_ID);

        $this->orderRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->orderMock);

        $this->orderMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::TEST_ORDER_ID);

        $this->checkoutSessionMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);

        $this->messageManagerMock
            ->expects($this->once())
            ->method('addError')
            ->with(RecoverCart::QUOTE_ERROR_MESSAGE)
            ->willReturnSelf();

        $this->checkoutSessionMock
            ->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                [SagePaySession::PRESAVED_PENDING_ORDER_KEY, null],
                [SagePaySession::CONVERTING_QUOTE_TO_ORDER, 0]
            );

        $this->recoverCart->setShouldCancelOrder(true)->execute();
    }
}
