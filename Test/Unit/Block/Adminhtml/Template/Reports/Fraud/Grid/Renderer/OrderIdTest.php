<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

use Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\OrderId;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class OrderIdTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\OrderId
     */
    private $orderIdRendererBlock;

    /**
     * @var OrderRepository
     */
    private $orderRepositoryMock;

    /**
     * @var Order
     */
    private $orderMock;

    /**
     * @var Context
     */
    private $contextMock;

    /**
     * @var DataObject
     */
    private $rowMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()->getMock();

        $columnMock = $this->getMockBuilder( Column::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->rowMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rowMock = new DataObject(['order_id' => 1]);

        $objectManagerHelper = new ObjectManager($this);
        $this->orderIdRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\OrderId',
            [
                'context' => $this->contextMock,
                'orderRepository' => $this->orderRepositoryMock,
            ]
        );

        $this->orderIdRendererBlock->setData('order_id', 1);
        $this->orderIdRendererBlock->setColumn($columnMock);
    }
    // @codingStandardsIgnoreEnd

    public function testRender()
    {
        $orderId = 1;

        $this->rowMock->getData('order_id');

        //$this->contextMock->render($this->rowMock);

        /*$numberMock = $this->getMockBuilder(AbstractRenderer::class)
            ->setMethods(['render'])
            ->disableOriginalConstructor()
            ->getMock();

        $numberMock->expects($this->once())->method('render')
            ->with($this->rowMock)
            ->willReturn($orderId);*/

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')->with($orderId)
            ->will($this->returnValue($this->orderMock));

        $this->assertEquals(
            '<a href=""></a>',
            $this->orderIdRendererBlock->render(new DataObject(['order_id' => 1]))
        );
    }
}
