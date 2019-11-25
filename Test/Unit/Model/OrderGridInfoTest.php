<?php
/**
 * Created by PhpStorm.
 * User: juan
 * Date: 2019-11-19
 * Time: 16:21
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\OrderGridInfo;
use Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\ThreeDSecure;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderGridInfoTest extends \PHPUnit_Framework_TestCase
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

    public function testThreeDSAuthenticated()
    {
        $orderTest = ['threeDStatus' => 'AUTHENTICATED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CHECK,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CHECK);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_CHECK);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_CHECK,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'AUTHENTICATED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThreeDSNotChecked()
    {
        $orderTest = ['threeDStatus' => 'NOTCHECKED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'NOTCHECKED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThreeDSNotAuthenticated()
    {
        $orderTest = ['threeDStatus' => 'NOTAUTHENTICATED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'NOTAUTHENTICATED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThreeDSError()
    {
        $orderTest = ['threeDStatus' => 'ERROR'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CROSS,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CROSS);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_CROSS);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_CROSS,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'ERROR'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThreeDSCardNotEnrolled()
    {
        $orderTest = ['threeDStatus' => 'CARDNOTENROLLED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'CARDNOTENROLLED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThreeDSIssuerNotEnrolled()
    {
        $orderTest = ['threeDStatus' => 'ISSUERNOTENROLLED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'ISSUERNOTENROLLED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThreeDSMalformedOrInvalid()
    {
        $orderTest = ['threeDStatus' => 'MALFORMEDORINVALID'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CROSS,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CROSS);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_CROSS);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_CROSS,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'MALFORMEDORINVALID'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThreeDSAttemptOnly()
    {
        $orderTest = ['threeDStatus' => 'ATTEMPTONLY'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'ATTEMPTONLY'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThreeDSNotAvailable()
    {
        $orderTest = ['threeDStatus' => 'NOTAVAILABLE'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'NOTAVAILABLE'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThreeDSIncomplete()
    {
        $orderTest = ['threeDStatus' => 'INCOMPLETE'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $threeDSColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getThreeDStatus'])
            ->getMock();

        $threeDSColumnMock->expects($this->once())->method('getThreeDStatus')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $threeDSColumnMock->prepareColumn($dataSource, "threeDStatus", "sagepay_threeDSecure");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_threeDSecure_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_threeDSecure_alt' => 'INCOMPLETE'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testAddressValidationMatched()
    {
        $orderTest = ['avsCvcCheckAddress' => 'MATCHED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CHECK,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CHECK);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $addressValidationColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $addressValidationColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_CHECK);

        $dataSource = self::DATA_SOURCE;

        $response = $addressValidationColumnMock->prepareColumn($dataSource, "avsCvcCheckAddress", "sagepay_addressValidation");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_addressValidation_src' => self::IMAGE_URL_CHECK,
                        'payment_method' => "sagepaysuite",
                        'sagepay_addressValidation_alt' => 'MATCHED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testAddressValidationNotChecked()
    {
        $orderTest = ['avsCvcCheckAddress' => 'NOTCHECKED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $addressValidationColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $addressValidationColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $addressValidationColumnMock->prepareColumn($dataSource, "avsCvcCheckAddress", "sagepay_addressValidation");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_addressValidation_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_addressValidation_alt' => 'NOTCHECKED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testAddressValidationNotProvided()
    {
        $orderTest = ['avsCvcCheckAddress' => 'NOTPROVIDED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $addressValidationColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $addressValidationColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $addressValidationColumnMock->prepareColumn($dataSource, "avsCvcCheckAddress", "sagepay_addressValidation");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_addressValidation_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_addressValidation_alt' => 'NOTPROVIDED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testAddressValidationNotMatched()
    {
        $orderTest = ['avsCvcCheckAddress' => 'NOTMATCHED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CROSS,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CROSS);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $addressValidationColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $addressValidationColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_CROSS);

        $dataSource = self::DATA_SOURCE;

        $response = $addressValidationColumnMock->prepareColumn($dataSource, "avsCvcCheckAddress", "sagepay_addressValidation");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_addressValidation_src' => self::IMAGE_URL_CROSS,
                        'payment_method' => "sagepaysuite",
                        'sagepay_addressValidation_alt' => 'NOTMATCHED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testAddressValidationPartial()
    {
        $orderTest = ['avsCvcCheckAddress' => 'PARTIAL'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_ZEBRA,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_ZEBRA);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $addressValidationColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $addressValidationColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_ZEBRA);

        $dataSource = self::DATA_SOURCE;

        $response = $addressValidationColumnMock->prepareColumn($dataSource, "avsCvcCheckAddress", "sagepay_addressValidation");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_addressValidation_src' => self::IMAGE_URL_ZEBRA,
                        'payment_method' => "sagepaysuite",
                        'sagepay_addressValidation_alt' => 'PARTIAL'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPostCodeCheckMatched()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'MATCHED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CHECK,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CHECK);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $postCodeCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_CHECK);

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckPostalCode", "sagepay_postcodeCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_CHECK,
                        'payment_method' => "sagepaysuite",
                        'sagepay_postcodeCheck_alt' => 'MATCHED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPostCodeNotChecked()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'NOTCHECKED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $postCodeCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckPostalCode", "sagepay_postcodeCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_postcodeCheck_alt' => 'NOTCHECKED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPostCodeNotProvided()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'NOTPROVIDED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $postCodeCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckPostalCode", "sagepay_postcodeCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_postcodeCheck_alt' => 'NOTPROVIDED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPostCodeNotMatched()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'NOTMATCHED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CROSS,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CROSS);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $postCodeCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_CROSS);

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckPostalCode", "sagepay_postcodeCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_CROSS,
                        'payment_method' => "sagepaysuite",
                        'sagepay_postcodeCheck_alt' => 'NOTMATCHED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPostCodePartial()
    {
        $orderTest = ['avsCvcCheckPostalCode' => 'PARTIAL'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_ZEBRA,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_ZEBRA);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $postCodeCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $postCodeCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_ZEBRA);

        $dataSource = self::DATA_SOURCE;

        $response = $postCodeCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckPostalCode", "sagepay_postcodeCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_postcodeCheck_src' => self::IMAGE_URL_ZEBRA,
                        'payment_method' => "sagepaysuite",
                        'sagepay_postcodeCheck_alt' => 'PARTIAL'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testCvTwoMatched()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'MATCHED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CHECK,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CHECK);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $cvTwoCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_CHECK);

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckSecurityCode", "sagepay_cvTwoCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_CHECK,
                        'payment_method' => "sagepaysuite",
                        'sagepay_cvTwoCheck_alt' => 'MATCHED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testCvTwoNotChecked()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'NOTCHECKED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $cvTwoCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckSecurityCode", "sagepay_cvTwoCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_cvTwoCheck_alt' => 'NOTCHECKED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testCvTwoNotProvided()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'NOTPROVIDED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_OUTLINE,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_OUTLINE);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $cvTwoCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_OUTLINE);

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckSecurityCode", "sagepay_cvTwoCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_OUTLINE,
                        'payment_method' => "sagepaysuite",
                        'sagepay_cvTwoCheck_alt' => 'NOTPROVIDED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testCvTwoNotMatched()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'NOTMATCHED'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_CROSS,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_CROSS);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $cvTwoCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_CROSS);

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckSecurityCode", "sagepay_cvTwoCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_CROSS,
                        'payment_method' => "sagepaysuite",
                        'sagepay_cvTwoCheck_alt' => 'NOTMATCHED'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testCvTwoPartial()
    {
        $orderTest = ['avsCvcCheckSecurityCode' => 'PARTIAL'];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(
            self::IMAGE_URL_ZEBRA,
            [
                '_secure' => true
            ]
        )
            ->willReturn(self::IMAGE_URL_ZEBRA);

        $orderMock = $this
            ->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock = $this
            ->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);

        $cvTwoCheckColumnMock = $this->getMockBuilder(OrderGridInfo::class)
            ->setConstructorArgs([
                'requestInterface' => $requestMock,
                'orderRepository' => $orderRepositoryMock,
                'suiteLogger' => $suiteLoggerMock,
                'assetRepository' => $assetRepositoryMock,
                [],
                []
            ])
            ->setMethods(['getStatusImage'])
            ->getMock();

        $cvTwoCheckColumnMock->expects($this->once())->method('getStatusImage')->willReturn(self::IMAGE_URL_ZEBRA);

        $dataSource = self::DATA_SOURCE;

        $response = $cvTwoCheckColumnMock->prepareColumn($dataSource, "avsCvcCheckSecurityCode", "sagepay_cvTwoCheck");

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_cvTwoCheck_src' => self::IMAGE_URL_ZEBRA,
                        'payment_method' => "sagepaysuite",
                        'sagepay_cvTwoCheck_alt' => 'PARTIAL'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }
}
