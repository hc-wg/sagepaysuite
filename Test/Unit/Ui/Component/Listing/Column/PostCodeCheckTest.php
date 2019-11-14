<?php
/**
 * Created by PhpStorm.
 * User: juan
 * Date: 2019-11-12
 * Time: 14:15
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\PostCodeCheck;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Ebizmarts\SagePaySuite\Helper\AdditionalInformation;

class PostCodeCheckTest extends \PHPUnit\Framework\TestCase
{
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

    public function testPostCodeCheckMatched()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'MATCHED'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CHECK,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CHECK);

        $orderMock = $this->createMock(OrderInterface::class);
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);
        $serializeMock = $this->createMock(AdditionalInformation::class);

        $postCodeCheckColumnMock = $this->getMockBuilder(PostCodeCheck::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                'serialize' => $serializeMock,
                [],
                []
            ])
            ->setMethods(['getPostCodeResult','getFieldName'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getPostCodeResult')->willReturn(self::IMAGE_URL_CHECK);
        $postCodeCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_postcodeCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareDataSource($dataSource);

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

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPostCodeCheckNotChecked()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'NOTCHECKED'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this->createMock(OrderInterface::class);
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);
        $serializeMock = $this->createMock(AdditionalInformation::class);

        $postCodeCheckColumnMock = $this->getMockBuilder(PostCodeCheck::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                'serialize' => $serializeMock,
                [],
                []
            ])
            ->setMethods(['getPostCodeResult','getFieldName'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getPostCodeResult')->willReturn(self::IMAGE_URL_OUTLINE);
        $postCodeCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_postcodeCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPostCodeCheckNotProvided()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'NOTPROVIDED'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this->createMock(OrderInterface::class);
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);
        $serializeMock = $this->createMock(AdditionalInformation::class);

        $postCodeCheckColumnMock = $this->getMockBuilder(PostCodeCheck::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                'serialize' => $serializeMock,
                [],
                []
            ])
            ->setMethods(['getPostCodeResult','getFieldName'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getPostCodeResult')->willReturn(self::IMAGE_URL_OUTLINE);
        $postCodeCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_postcodeCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPostCodeCheckNotMatched()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'NOTMATCHED'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CROSS,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CROSS);

        $orderMock = $this->createMock(OrderInterface::class);
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);
        $serializeMock = $this->createMock(AdditionalInformation::class);

        $postCodeCheckColumnMock = $this->getMockBuilder(PostCodeCheck::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                'serialize' => $serializeMock,
                [],
                []
            ])
            ->setMethods(['getPostCodeResult','getFieldName'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getPostCodeResult')->willReturn(self::IMAGE_URL_CROSS);
        $postCodeCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_postcodeCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_CROSS,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPostCodeCheckPartial()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'PARTIAL'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_ZEBRA,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_ZEBRA);

        $orderMock = $this->createMock(OrderInterface::class);
        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);
        $serializeMock = $this->createMock(AdditionalInformation::class);

        $postCodeCheckColumnMock = $this->getMockBuilder(PostCodeCheck::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                'serialize' => $serializeMock,
                [],
                []
            ])
            ->setMethods(['getPostCodeResult','getFieldName'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getPostCodeResult')->willReturn(self::IMAGE_URL_ZEBRA);
        $postCodeCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_postcodeCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_ZEBRA,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

}
