<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Tokens\Grid\Renderer;

class ActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Tokens\Grid\Renderer\Action
     */
    private $actionRendererBlock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->actionRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Tokens\Grid\Renderer\Action',
            []
        );

        $this->actionRendererBlock->setColumn($columnMock);
    }
    // @codingStandardsIgnoreEnd

    public function testRender()
    {
        $this->assertEquals(
            "&nbsp;",
            $this->actionRendererBlock->render(new \Magento\Framework\DataObject)
        );
    }
}
