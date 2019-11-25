<?php
/**
 * Created by PhpStorm.
 * User: juan
 * Date: 2019-11-12
 * Time: 14:03
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Model\OrderGridInfo;
use Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\AddressValidation;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;

class AddressValidationTest extends \PHPUnit_Framework_TestCase
{
    const FIELD_NAME = "sagepay_addressValidation";
    const INDEX = "avsCvcCheckAddress";
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
        $orderGridInfoMock = $this
            ->getMockBuilder(OrderGridInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_addressValidation_src' => self::IMAGE_URL_CHECK,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $orderGridInfoMock
            ->expects($this->once())
            ->method('prepareColumn')
            ->with(self::DATA_SOURCE, self::INDEX, self::FIELD_NAME)
            ->willReturn($expectedResponse);

        $addressValidationMock = $this->getMockBuilder(AddressValidation::class)
            ->setConstructorArgs([
                'orderGridInfo' => $orderGridInfoMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                [],
                []
            ])
            ->setMethods(['getFieldName'])
            ->getMock();

        $addressValidationMock
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn(self::FIELD_NAME);

        $response = $addressValidationMock->prepareDataSource(self::DATA_SOURCE);

        $this->assertEquals($expectedResponse, $response);
    }

}
