<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

class RulesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Rules
     */
    private $rulesRendererBlock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->rulesRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Rules',
            []
        );

        $this->rulesRendererBlock->setColumn($columnMock);
    }
    // @codingStandardsIgnoreEnd

    public function testRenderEmpty()
    {
        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();
        $rowMock->expects($this->once())
            ->method('getData')
            ->with('additional_information')
            ->willReturn([]);

        $this->assertEquals(
            '',
            $this->rulesRendererBlock->render($rowMock)
        );
    }

    public function testRenderNotEmpty()
    {
        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();
        $rowMock->expects($this->once())
            ->method('getData')
            ->with('additional_information')
            ->willReturn('a:1:{s:10:"fraudrules";a:0:{}}');

        $this->assertEquals(
            [],
            $this->rulesRendererBlock->render($rowMock)
        );
    }
}
