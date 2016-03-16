<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

class OrderIdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\OrderId
     */
    protected $orderIdRendererBlock;

    protected function setUp()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderMock));

        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->orderIdRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\OrderId',
            [
                "orderFactory" => $orderFactoryMock
            ]
        );

        $this->orderIdRendererBlock->setColumn($columnMock);
    }

    public function testRender()
    {
        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(
            '<a href=""></a>',
            $this->orderIdRendererBlock->render($rowMock)
        );
    }
}