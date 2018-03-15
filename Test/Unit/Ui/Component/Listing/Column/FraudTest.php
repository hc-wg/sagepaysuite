<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Ui\Component\Listing\Column;

use bar\foo\baz\Object;
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
    const ENTITY_VALUE = 1;

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
            "check -10" => ['check.png', -10]
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
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_VALUE)->willThrowException($noSuchEntityException);

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

        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_VALUE
                    ]
                ]
            ]
        ];

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
            ['Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\Fraud::prepareDataSource', 68]
        );
        return $suiteLoggerMock;
    }

    private function makeSuiteLoggerMockNoSuchEntityException($noSuchEntityException)
    {
        $suiteLoggerMock = $this->createMock(Logger::class);
        $suiteLoggerMock->expects($this->once())->method('logException')->with(
            $noSuchEntityException,
            ['Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\Fraud::prepareDataSource', 71]
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
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_VALUE)->willReturn($orderMock);
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

        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_VALUE
                    ]
                ]
            ]
        ];

        $this->assertEquals(['data' => ['items' => [['entity_id' => self::ENTITY_VALUE]]]], $fraudColumnMock->prepareDataSource($dataSource));
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
            ->willReturn('adsdsaadsasd');

        $orderMock = $this->createMock(OrderInterface::class);

        $paymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderRepositoryMock->expects($this->once())->method('get')->with(self::ENTITY_VALUE)->willReturn($orderMock);
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
        $fraudColumnMock->expects($this->once())->method('getFieldName')->willReturn('fieldname');

        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_VALUE
                    ]
                ]
            ]
        ];


        $response = $fraudColumnMock->prepareDataSource($dataSource);

        $expectedResponse = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => self::ENTITY_VALUE,
                        'fieldname_src' => 'adsdsaadsasd'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }


}
