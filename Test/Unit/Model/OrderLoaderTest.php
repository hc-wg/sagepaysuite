<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Model\OrderLoader;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

class OrderLoaderTest extends TestCase
{
    const RESERVER_ORDER_ID = "10000000024";
    const STORE_ID = 1;
    const ORDER_ID = 231;

    /** @var Quote */
    private $quoteMock;

    /** @var Order */
    private $orderMock;

    /** @var OrderFactory */
    private $orderFactoryMock;

    /** @var ObjectManagerHelper */
    private $objectManagerHelper;

    private $sut;

    public function setUp()
    {
        $this->quoteMock = $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock
            ->expects($this->once())
            ->method('getReservedOrderId')
            ->willReturn(self::RESERVER_ORDER_ID);
        $this->quoteMock
            ->expects($this->once())
            ->method('getStoreId')
            ->willReturn(self::STORE_ID);

        $this->orderMock = $this
            ->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderFactoryMock = $this
            ->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $this->orderMock
            ->expects($this->once())
            ->method('loadByIncrementIdAndStoreId')
            ->with(self::RESERVER_ORDER_ID, self::STORE_ID)
            ->willReturnSelf();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->sut = $this->objectManagerHelper->getObject(
            OrderLoader::class,
            [
                'orderFactory' => $this->orderFactoryMock
            ]
        );
    }

    public function testLoadOrderFromQuoteSuccess()
    {
        $this->orderMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::ORDER_ID);

        $this->sut->loadOrderFromQuote($this->quoteMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testLoadOrderFromQuoteException()
    {
        $this->orderMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->sut->loadOrderFromQuote($this->quoteMock);
    }
}
