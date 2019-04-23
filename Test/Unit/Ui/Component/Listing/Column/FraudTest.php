<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\Fraud;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class FraudTest extends \PHPUnit\Framework\TestCase
{

    const IMAGE_PATH = 'Ebizmarts_SagePaySuite::images/icon-shield-';
    const ENTITY_ID = 1;
    const IMAGE_URL_TEST = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/test.png';
    const IMAGE_URL_WAITING = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/waiting.png';
    const IMAGE_URL_CHECK = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/icon-shield-check.png';
    const IMAGE_URL_CROSS = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/icon-shield-cross.png';
    const IMAGE_URL_ZEBRA = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/icon-shield-zebra.png';
    const IMAGE_URL_NOTCHECKED = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/icon-shield-outline.png';
    const IMAGE_URL_INVALID = 'https://example.com/adminhtml/Magento/backend/en_US/Ebizmarts_SagePaySuite/images/icon-shield-';
    const DATA_SOURCE = [
        'data' => [
            'items' => [
                [
                    'entity_id' => self::ENTITY_ID
                ]
            ]
        ]
    ];

    /**
     * @dataProvider thirdmanDataProvider
     */
    public function testGetImageNameThirdman($image, $score)
    {
        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->disableOriginalConstructor()
            ->setMethods(['getImageNameRed'])
            ->getMock();

        $this->assertEquals(self::IMAGE_PATH . $image, $fraudColumnMock->getImageNameThirdman($score));
    }

    /**
     * @dataProvider redDataProvider
     */
    public function testGetImageNameRed($image, $score)
    {
        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->disableOriginalConstructor()
            ->setMethods(['getImageNameThirdman'])
            ->getMock();

        $this->assertEquals(self::IMAGE_PATH . $image, $fraudColumnMock->getImageNameRed($score));
    }

    public function thirdmanDataProvider()
    {
        return [
            "cross 50" => ['cross.png', 50],
            "cross 80" => ['cross.png', 80],
            "zebra 49" => ['zebra.png', 49],
            "zebra 30" => ['zebra.png', 30],
            "zebra 45" => ['zebra.png', 45],
            "check 0" => ['check.png', 0],
            "check 29" => ['check.png', 29],
            "check -10" => ['check.png', -10],
            "invalid" => ['', 'not a number']
        ];
    }

    public function redDataProvider()
    {
        return [
            "cross" => ['cross.png', 'DENY'],
            "zebra" => ['zebra.png', 'CHALLENGE'],
            "outline" => ['outline.png', 'NOTCHECKED'],
            "check" => ['check.png', 'ACCEPT']
        ];
    }

    public function testInvalidArgumentOrderId()
    {
        $inputException = new InputException(__('Id required'));

        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(false)->willThrowException($inputException);

        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $assetRepositoryMock = $this->createMock(Repository::class);

        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $this->makeSuiteLoggerMockInvalidArgument($inputException),
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethods(['getImageNameRed'])
            ->getMock();

        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => false
                    ]
                ]
            ]
        ];

        $fraudColumnMock->prepareDataSource($dataSource);
    }

    public function testNoSuchEntityException()
    {
        $noSuchEntityException = new NoSuchEntityException(__('Requested entity doesn\'t exist'));

        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willThrowException($noSuchEntityException);

        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $assetRepositoryMock = $this->createMock(Repository::class);

        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $this->makeSuiteLoggerMockNoSuchEntityException($noSuchEntityException),
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethods(['getImageNameRed'])
            ->getMock();

        $dataSource = self::DATA_SOURCE;

        $fraudColumnMock->prepareDataSource($dataSource);
    }

    /**
     * @param $inputException
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeSuiteLoggerMockInvalidArgument($inputException)
    {
        $suiteLoggerMock = $this->createMock(Logger::class);
        $suiteLoggerMock->expects($this->once())->method('logException')->with(
            $inputException,
            ['Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\Fraud::prepareDataSource', 67]
        );
        return $suiteLoggerMock;
    }

    private function makeSuiteLoggerMockNoSuchEntityException($noSuchEntityException)
    {
        $suiteLoggerMock = $this->createMock(Logger::class);
        $suiteLoggerMock->expects($this->once())->method('logException')->with(
            $noSuchEntityException,
            ['Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\Fraud::prepareDataSource', 70]
        );
        return $suiteLoggerMock;
    }

    public function testGetPaymentNull()
    {
        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure');

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->never())->method('getUrlWithParams');

        $orderMock = $this->createMock(OrderInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn(null);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['prepareDataSource'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameThirdman');
        $fraudColumnMock->expects($this->never())->method('getImageNameRed');

        $dataSource = self::DATA_SOURCE;

        $this->assertEquals(['data' => ['items' => [['entity_id' => self::ENTITY_ID]]]], $fraudColumnMock->prepareDataSource($dataSource));
    }


    public function testRedAcept()
    {
        $orderTest = ['fraudcode' => 'ACCEPT'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(self::IMAGE_PATH . 'check.png',
            [
                '_secure' => true
            ])
            ->willReturn(self::IMAGE_URL_CHECK);


        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['getImageNameRed', 'prepareDataSource'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameThirdman');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_CHECK
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testRedDeny()
    {
        $orderTest = ['fraudcode' => 'DENY'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(self::IMAGE_PATH . 'cross.png',
            [
                '_secure' => true
            ])
            ->willReturn(self::IMAGE_URL_CROSS);


        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['getImageNameRed', 'prepareDataSource'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameThirdman');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_CROSS
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testRedChallenge()
    {
        $orderTest = ['fraudcode' => 'CHALLENGE'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(self::IMAGE_PATH . 'zebra.png',
            [
                '_secure' => true
            ])
            ->willReturn(self::IMAGE_URL_ZEBRA);


        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['getImageNameRed', 'prepareDataSource'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameThirdman');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_ZEBRA
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testRedNotChecked()
    {
        $orderTest = ['fraudcode' => 'NOTCHECKED'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(self::IMAGE_PATH . 'outline.png',
            [
                '_secure' => true
            ])
            ->willReturn(self::IMAGE_URL_NOTCHECKED);


        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['getImageNameRed', 'prepareDataSource'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameThirdman');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_NOTCHECKED
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }


    public function testThirdManCheck()
    {
        $orderTest = ['fraudcode' => 10, 'fraudrules' => 'rule'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(self::IMAGE_PATH . 'check.png',
            [
                '_secure' => true
            ])
            ->willReturn(self::IMAGE_URL_CHECK);

        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['getImageNameThirdman', 'prepareDataSource'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameRed');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_CHECK,
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThirdManZebra()
    {
        $orderTest = ['fraudcode' => 30, 'fraudrules' => 'rule'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(self::IMAGE_PATH . 'zebra.png',
            [
                '_secure' => true
            ])
            ->willReturn(self::IMAGE_URL_ZEBRA);

        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['getImageNameThirdman', 'prepareDataSource'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameRed');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_ZEBRA,
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThirdManCross()
    {
        $orderTest = ['fraudcode' => 50, 'fraudrules' => 'rule'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(self::IMAGE_PATH . 'cross.png',
            [
                '_secure' => true
            ])
            ->willReturn(self::IMAGE_URL_CROSS);

        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['getImageNameThirdman', 'prepareDataSource'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameRed');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_CROSS,
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testThirdManFraudCodeInvalid()
    {
        $orderTest = ['fraudcode' => "Not a number", 'fraudrules' => 'rule'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock->expects($this->once())->method('getUrlWithParams')->with(self::IMAGE_PATH . '',
            [
                '_secure' => true
            ])
            ->willReturn(self::IMAGE_URL_INVALID);

        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['getImageNameThirdman', 'prepareDataSource'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameRed');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_INVALID,
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testTestFlag()
    {
        $orderTest = ['mode' => 'test'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock
            ->expects($this->once())
            ->method('getUrlWithParams')
            ->with('Ebizmarts_SagePaySuite::images/test.png',
            [
                '_secure' => true
            ])
            ->willReturn(self::IMAGE_URL_TEST);

        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['prepareDataSource', 'getTestImage'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameRed');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_TEST,
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    public function testWaitingFlag()
    {
        $orderTest = ['mode' => 'live'];

        $suiteLoggerMock = $this->createMock(Logger::class);
        $orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->expects($this->once())->method('isSecure')->willReturn(true);

        $assetRepositoryMock = $this->createMock(Repository::class);
        $assetRepositoryMock
            ->expects($this->once())
            ->method('getUrlWithParams')
            ->with('Ebizmarts_SagePaySuite::images/waiting.png',
                [
                    '_secure' => true
                ])
            ->willReturn(self::IMAGE_URL_WAITING);

        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_ID)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $paymentMock->expects($this->once())->method('getAdditionalInformation')->willReturn($orderTest);


        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->setConstructorArgs([
                'suiteLogger' => $suiteLoggerMock,
                'context' => $contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'orderRepository' => $orderRepositoryMock,
                'assetRepository' => $assetRepositoryMock,
                'requestInterface' => $requestMock,
                [],
                []
            ])
            ->setMethodsExcept(['prepareDataSource', 'getWaitingImage'])
            ->getMock();

        $fraudColumnMock->expects($this->never())->method('getImageNameRed');
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('sagepay_fraud');

        $dataSource = self::DATA_SOURCE;

        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_ID,
                        'sagepay_fraud_src' => self::IMAGE_URL_WAITING,
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }
}
