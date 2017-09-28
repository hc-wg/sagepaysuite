<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

class RecommendationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param $data
     * @param $color
     * @param $recommendation
     * @dataProvider dataProvider
     */
    public function testRender($data, $color, $recommendation)
    {
        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $recommendationRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Recommendation',
            []
        );

        $recommendationRendererBlock->setColumn($columnMock);

        $rowMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->disableOriginalConstructor()
            ->getMock();
        $rowMock->expects($this->once())
            ->method('getData')
            ->with('additional_information')
            ->willReturn($data);

        $this->assertEquals(
            '<span style="color:' . $color . ';">' . $recommendation . '</span>',
            $recommendationRendererBlock->render($rowMock)
        );
    }

    public function dataProvider()
    {
        return [
            [
                'data'  => 'a:1:{s:25:"fraudscreenrecommendation";s:6:"REJECT";}',
                'color' => 'red',
                'recommendation' => 'REJECT',
            ],
            [
                'data'  => 'a:1:{s:25:"fraudscreenrecommendation";s:4:"DENY";}',
                'color' => 'red',
                'recommendation' => 'DENY',
            ],
            [
                'data'  => 'a:1:{s:25:"fraudscreenrecommendation";s:9:"CHALLENGE";}',
                'color' => 'orange',
                'recommendation' => 'CHALLENGE',
            ],
            [
                'data'  => 'a:1:{s:25:"fraudscreenrecommendation";s:4:"HOLD";}',
                'color' => 'orange',
                'recommendation' => 'HOLD',
            ]
        ];
    }
}
