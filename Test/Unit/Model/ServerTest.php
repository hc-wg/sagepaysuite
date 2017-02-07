<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Server
     */
    private $serverModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    private $paymentOpsMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentOpsMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->serverModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Server',
            [
                "config"     => $this->configMock,
                "paymentOps" => $this->paymentOpsMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testCapture()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentOpsMock
            ->expects($this->once())
            ->method('capture')
            ->with($paymentMock, 100);

        $this->serverModel->capture($paymentMock, 100);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage There was an error.
     */
    public function testCaptureERROR()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $exception = new \Magento\Framework\Exception\LocalizedException(
            new \Magento\Framework\Phrase("There was an error.")
        );

        $this->paymentOpsMock
            ->expects($this->once())
            ->method('capture')
            ->with($paymentMock, 100)
            ->willThrowException($exception);

        $this->serverModel->capture($paymentMock, 100);
    }

    public function testMarkAsInitialized()
    {
        $this->serverModel->markAsInitialized();
        $this->assertEquals(
            false,
            $this->serverModel->isInitializeNeeded()
        );
    }

    public function testRefund()
    {
       $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentOpsMock
            ->expects($this->once())
            ->method('refund')
            ->with($paymentMock, 100);

        $this->serverModel->refund($paymentMock, 100);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Error in Refunding.
     */
    public function testRefundERROR()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $exception = new \Magento\Framework\Exception\LocalizedException(
            new \Magento\Framework\Phrase("Error in Refunding.")
        );

        $this->paymentOpsMock
            ->expects($this->once())
            ->method('refund')
            ->with($paymentMock, 100)
            ->willThrowException($exception);

        $this->serverModel->refund($paymentMock, 100);
    }

    public function testCancel()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serverModel->setInfoInstance($paymentMock);

        $this->assertEquals(
            $this->serverModel,
            $this->serverModel->cancel($paymentMock)
        );
    }

    public function testInitialize()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('setCanSendNewEmailFlag')
            ->with(false);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));

        $stateMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->setMethods(["offsetExists","offsetGet","offsetSet","offsetUnset","setStatus","setIsNotified"])
            ->disableOriginalConstructor()
            ->getMock();
        $stateMock->expects($this->once())
            ->method('setStatus')
            ->with('pending_payment');
        $stateMock->expects($this->once())
            ->method('setIsNotified')
            ->with(false);

        $this->serverModel->setInfoInstance($paymentMock);
        $this->serverModel->initialize("", $stateMock);
    }

    public function testGetConfigPaymentAction()
    {
        $this->configMock->expects($this->once())
            ->method('getPaymentAction');
        $this->serverModel->getConfigPaymentAction();
    }
}
