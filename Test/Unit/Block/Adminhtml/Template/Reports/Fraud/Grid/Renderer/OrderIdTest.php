<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class OrderIdTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ORDER_ENTITY_ID = 1;
    const TEST_ORDER_INCREMENT_ID = 100000098;
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\OrderId
     */
    private $orderIdRendererBlock;

    /**
     * @var OrderRepository | \PHPUnit_Framework_MockObject_MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var Order | \PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var Context | \PHPUnit_Framework_MockObject_MockObject
     */
    private $contextMock;

    /**
     * @var DataObject | \PHPUnit_Framework_MockObject_MockObject
     */
    private $rowMock;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger | \PHPUnit_Framework_MockObject_MockObject
     */
    private $suiteLoggerMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->suiteLoggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        //$this->MakeContextMock($urlBuilderMock);
        $this->contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->setMethods(['getUrlBuilder'])->getMock();

        $columnMock = $this->getMockBuilder(Column::class)
            ->disableOriginalConstructor()
            ->setMethods(["getIndex"])
            ->getMock();
        $columnMock->method("getIndex")->willReturn("order_id");

        $this->orderRepositoryMock = $this
            ->getMockBuilder(OrderRepository::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->setMethods(['getEntityId', 'getIncrementId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rowMock = new DataObject(['order_id' => self::TEST_ORDER_ENTITY_ID]);

        $objectManagerHelper = new ObjectManager($this);
        $this->orderIdRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\OrderId',
            [
                'context' => $this->contextMock,
                'orderRepository' => $this->orderRepositoryMock,
                'suiteLogger' => $this->suiteLoggerMock,
            ]
        );

        $this->orderIdRendererBlock->setData('order_id', self::TEST_ORDER_ENTITY_ID);
        $this->orderIdRendererBlock->setColumn($columnMock);
    }

    // @codingStandardsIgnoreEnd

    public function testRender()
    {
        $this->orderRepositoryMock->expects($this->once())
            ->method('get')->with(self::TEST_ORDER_ENTITY_ID)
            ->willReturn($this->orderMock);

        $urlBuilderMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->once())->method('getUrlBuilder')->willReturn($urlBuilderMock);

        $urlBuilderMock->expects($this->once())->method("getUrl")
            ->with('sales/order/view/', ['order_id' => self::TEST_ORDER_ENTITY_ID])
            ->willReturn("https://example.comsales/order/view/order_id/1");

        $this->orderMock->expects($this->once())->method('getEntityId')->willReturn(self::TEST_ORDER_ENTITY_ID);
        $this->orderMock->expects($this->once())->method('getIncrementId')->willReturn(self::TEST_ORDER_INCREMENT_ID);

        $this->assertEquals(
            '<a href="https://example.comsales/order/view/order_id/1">' . self::TEST_ORDER_INCREMENT_ID . '</a>',
            $this->orderIdRendererBlock
                ->render(new DataObject(['order_id' => self::TEST_ORDER_ENTITY_ID]))
        );
    }

    public function testRenderNoSuchEntityException()
    {
        $exceptionMessage = "The entity that was requested doesn't exist. Verify the entity and try again.";
        $noSuchEntityException = new NoSuchEntityException(__($exceptionMessage));

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')->with(self::TEST_ORDER_ENTITY_ID)
            ->willThrowException($noSuchEntityException);

        $this->assertEquals(
            '',
            $this->orderIdRendererBlock->render(new DataObject(['order_id' => self::TEST_ORDER_ENTITY_ID]))
        );
    }

    public function testRenderInputException()
    {
        $exceptionMessage = 'An ID is needed. Set the ID and try again.';
        $InputException = new InputException(__($exceptionMessage));

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')->with(self::TEST_ORDER_ENTITY_ID)
            ->willThrowException($InputException);

        $this->assertEquals(
            '',
            $this->orderIdRendererBlock->render(new DataObject(['order_id' => self::TEST_ORDER_ENTITY_ID]))
        );
    }
}