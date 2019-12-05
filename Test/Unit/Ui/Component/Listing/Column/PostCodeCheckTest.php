<?php
/**
 * Copyright © 2019 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\OrderGridColumns;
use Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\PostCodeCheck;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use \Ebizmarts\SagePaySuite\Model\OrderGridInfo;

class PostCodeCheckTest extends \PHPUnit_Framework_TestCase
{
    const FIELD_NAME = "sagepay_postcodeCheck";
    const INDEX = "avsCvcCheckPostalCode";
    const ENTITY_ID = 1;
    const IMAGE_URL_CHECK = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/icon-shield-check.png';
    const IMAGE_URL_CROSS = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/icon-shield-cross.png';
    const IMAGE_URL_ZEBRA = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/icon-shield-zebra.png';
    const IMAGE_URL_OUTLINE = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/icon-shield-outline.png';
    const DATA_SOURCE = [
        'data' => [
            'items' => [
                [
                    'entity_id' => self::ENTITY_ID,
                    'payment_method' => "sagepaysuite"
                ]
            ]
        ]
    ];

    public function testPrepareDataSource()
    {
        $contextMock = $this
            ->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->once())
            ->method('getProcessor')
            ->willReturn(
                $this
                    ->getMockBuilder(Processor::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            );

        $uiComponentFactoryMock = $this
            ->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderGridColumnsMock = $this
            ->getMockBuilder(OrderGridColumns::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_CHECK,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $orderGridColumnsMock
            ->expects($this->once())
            ->method('prepareColumn')
            ->with(self::DATA_SOURCE, self::INDEX, self::FIELD_NAME)
            ->willReturn($expectedResponse);

        $postCodeCheckMock = $this->getMockBuilder(PostCodeCheck::class)
            ->setConstructorArgs([
                'orderGridColumns' => $orderGridColumnsMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                [],
                []
            ])
            ->setMethods(['getFieldName'])
            ->getMock();

        $postCodeCheckMock
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn(self::FIELD_NAME);

        $response = $postCodeCheckMock->prepareDataSource(self::DATA_SOURCE);

        $this->assertEquals($expectedResponse, $response);
    }

}
