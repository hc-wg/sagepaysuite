<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

class DetailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Detail
     */
    private $detailRendererBlock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->detailRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Detail',
            []
        );

        $this->detailRendererBlock->setColumn($columnMock);
    }
    // @codingStandardsIgnoreEnd

    public function testRender()
    {
        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();
        $rowMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('a:1:{s:15:"fraudcodedetail";s:6:"detail";}'));

        $this->assertEquals(
            'detail',
            $this->detailRendererBlock->render($rowMock)
        );
    }
}
