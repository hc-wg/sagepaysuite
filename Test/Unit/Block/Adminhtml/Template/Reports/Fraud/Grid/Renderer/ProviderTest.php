<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

class ProviderTest extends \PHPUnit_Framework_TestCase
{

    public function testRender()
    {
        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $blockMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Provider::class)
            ->setMethods(['getViewFileUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $blockMock->setColumn($columnMock);

        $blockMock
            ->expects($this->once())
            ->method('getViewFileUrl')
            ->with('Ebizmarts_SagePaySuite::images/red_logo.png')
            ->willReturn('Ebizmarts_SagePaySuite/images/red_logo.png');

        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();
        $rowMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('a:1:{s:17:"fraudprovidername";s:3:"ReD";}'));

        $this->assertEquals(
            '<img style="height: 20px;" src="Ebizmarts_SagePaySuite/images/red_logo.png">',
            $blockMock->render($rowMock)
        );
    }

    public function testRenderT3M()
    {
        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $blockMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Provider::class)
            ->setMethods(['getViewFileUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $blockMock->setColumn($columnMock);

        $blockMock
            ->expects($this->once())
            ->method('getViewFileUrl')
            ->with('Ebizmarts_SagePaySuite::images/t3m_logo.png')
            ->willReturn('Ebizmarts_SagePaySuite/images/t3m_logo.png');

        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();
        $rowMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('a:1:{s:17:"fraudprovidername";s:3:"T3M";}'));

        $this->assertEquals(
            '<span><img style="height: 20px;vertical-align: text-top;" src="Ebizmarts_SagePaySuite/images/t3m_logo.png"> T3M</span>',
            $blockMock->render($rowMock)
        );
    }
}
