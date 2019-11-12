<?php
/**
 * Created by PhpStorm.
 * User: juan
 * Date: 2019-11-12
 * Time: 14:24
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\CvTwoCheck;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Ebizmarts\SagePaySuite\Helper\AdditionalInformation;

class CvTwoCheckTest extends \PHPUnit\Framework\TestCase
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

    public function testAddressValidationMatched()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'MATCHED'];

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

        $cvTwoCheckColumnMock = $this->getMockBuilder(CvTwoCheck::class)
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
            ->setMethods(['getCvTwoCheck','getFieldName'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getCvTwoCheck')->willReturn(self::IMAGE_URL_CHECK);
        $cvTwoCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_cvTwoCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_CHECK,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testAddressValidationNotchecked()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'NOTCHECKED'];

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

        $cvTwoCheckColumnMock = $this->getMockBuilder(CvTwoCheck::class)
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
            ->setMethods(['getCvTwoCheck','getFieldName'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getCvTwoCheck')->willReturn(self::IMAGE_URL_OUTLINE);
        $cvTwoCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_cvTwoCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testAddressValidationNotProvided()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'NOTPROVIDED'];

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

        $cvTwoCheckColumnMock = $this->getMockBuilder(CvTwoCheck::class)
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
            ->setMethods(['getCvTwoCheck','getFieldName'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getCvTwoCheck')->willReturn(self::IMAGE_URL_OUTLINE);
        $cvTwoCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_cvTwoCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testAddressValidationNotMatched()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'NOTMATCHED'];

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

        $cvTwoCheckColumnMock = $this->getMockBuilder(CvTwoCheck::class)
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
            ->setMethods(['getCvTwoCheck','getFieldName'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getCvTwoCheck')->willReturn(self::IMAGE_URL_CROSS);
        $cvTwoCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_cvTwoCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_CROSS,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testAddressValidationPartial()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'PARTIAL'];

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

        $cvTwoCheckColumnMock = $this->getMockBuilder(CvTwoCheck::class)
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
            ->setMethods(['getCvTwoCheck','getFieldName'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getCvTwoCheck')->willReturn(self::IMAGE_URL_ZEBRA);
        $cvTwoCheckColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_cvTwoCheck');

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_ZEBRA,
                        'payment_method' => "sagepaysuite"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

}