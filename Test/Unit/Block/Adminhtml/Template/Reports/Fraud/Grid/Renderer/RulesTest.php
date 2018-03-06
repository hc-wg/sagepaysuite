<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RulesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Rules
     */
    private $rulesRendererBlock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $objectManagerHelper = new ObjectManager($this);

        $columnMock = $this
            ->getMockBuilder('Magento\Backend\Block\Widget\Grid\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this
            ->getMockBuilder('\Magento\Backend\Block\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $serializerMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize'])
            ->getMock();

        $loggerMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $additionalInformation = $objectManagerHelper
            ->getObject(\Ebizmarts\SagePaySuite\Helper\AdditionalInformation::class,
                [
                    'serializer' => $serializerMock,
                    'logger' => $loggerMock
                ]
            );

        $this->rulesRendererBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Template\Reports\Fraud\Grid\Renderer\Rules',
            [
                'context' => $contextMock,
                'information' => $additionalInformation,
                []
            ]
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
            ->willReturn("");

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
            ->willReturn('{"fraudrules":"Sage Pay Direct","statusCode":"0000"}');

        $this->assertEquals(
            "Sage Pay Direct",
            $this->rulesRendererBlock->render($rowMock)
        );
    }
}
