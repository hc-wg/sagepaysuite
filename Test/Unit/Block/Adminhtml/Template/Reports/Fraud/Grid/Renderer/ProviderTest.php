<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

class ProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Provider
     */
    private $providerRendererBlock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->providerRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Provider',
            []
        );

        $this->providerRendererBlock->setColumn($columnMock);
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
            ->will($this->returnValue('a:1:{s:17:"fraudprovidername";s:3:"ReD";}'));

        $this->assertEquals(
            '<img style="height: 20px;" src="">',
            $this->providerRendererBlock->render($rowMock)
        );
    }
}
