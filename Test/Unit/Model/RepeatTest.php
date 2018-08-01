<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RepeatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Repeat
     */
    private $repeatModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedApiMock;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /** @var \Ebizmarts\SagePaySuite\Model\Payment|\PHPUnit_Framework_MockObject_MockObject */
    private $paymentsOpsMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Shared')
            ->disableOriginalConstructor()
            ->getMock();

        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock->expects($this->any())
            ->method('clearTransactionId')
            ->will($this->returnValue(self::TEST_VPSTXID));

        $this->paymentsOpsMock = $this->getMockBuilder('\Ebizmarts\SagePaySuite\Model\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->repeatModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Repeat',
            [
                'config'      => $this->configMock,
                'sharedApi'   => $this->sharedApiMock,
                'suiteHelper' => $suiteHelperMock,
                'paymentOps'  => $this->paymentsOpsMock
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
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(1));
        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->with('paymentAction')
            ->will($this->returnValue(Config::ACTION_REPEAT_DEFERRED));

        $this->paymentsOpsMock->expects($this->once())->method('capture')->with($paymentMock, 100);

        $this->repeatModel->capture($paymentMock, 100);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testCaptureERROR()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(2));
        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->with('paymentAction')
            ->will($this->returnValue(Config::ACTION_REPEAT_DEFERRED));

        $exceptionMock = $this->getMockBuilder('\Magento\Framework\Exception\LocalizedException')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentsOpsMock
            ->expects($this->once())
            ->method('capture')
            ->with($paymentMock, 100)
            ->willThrowException($exceptionMock);

        $this->repeatModel->capture($paymentMock, 100);
    }

    public function testRefund()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentsOpsMock
            ->expects($this->once())
            ->method('refund')
            ->with($paymentMock, 100);

        $this->repeatModel->refund($paymentMock, 100);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testRefundERROR()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $exceptionMock = $this->getMockBuilder('\Magento\Framework\Exception\LocalizedException')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentsOpsMock
            ->expects($this->once())
            ->method('refund')
            ->with($paymentMock, 100)
            ->willThrowException($exceptionMock);;

        $this->repeatModel->refund($paymentMock, 100);
    }

    public function testGetConfigPaymentAction()
    {
        $this->configMock->expects($this->once())
            ->method('getPaymentAction');
        $this->repeatModel->getConfigPaymentAction();
    }

    public function testInitialize()
    {
        $orderMock = $this->makeOrderMockNoSendNewEmail();

        $paymentMock = $this->makePaymentMockForInitialize($orderMock);

        $stateMock = $this->makeStateObjectMock();
        $stateMock->expects($this->once())
            ->method('setStatus')
            ->with('pending_payment');
        $stateMock->expects($this->once())
            ->method('setState')
            ->with('pending_payment');
        $stateMock->expects($this->once())
            ->method('setIsNotified')
            ->with(false);

        $this->repeatModel->setInfoInstance($paymentMock);
        $this->repeatModel->initialize("Payment", $stateMock);
    }

    private function makeStateObjectMock()
    {
        $stateMock = $this->getMockBuilder('Magento\Framework\DataObject')->setMethods([
            "offsetExists",
            "offsetGet",
            "offsetSet",
            "offsetUnset",
            "setStatus",
            "setState",
            "setIsNotified"
        ])->disableOriginalConstructor()->getMock();

        return $stateMock;
    }

    /**
     * @param $orderMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makePaymentMockForInitialize($orderMock)
    {
        $paymentMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')->disableOriginalConstructor()->getMock();
        $paymentMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));

        return $paymentMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeOrderMockNoSendNewEmail()
    {
        $orderMock = $this->getMockBuilder('Magento\Sales\Model\Order')->disableOriginalConstructor()->getMock();
        $orderMock->expects($this->once())->method('setCanSendNewEmailFlag')->with(false);

        return $orderMock;
    }

    public function testMarkAsInitialized()
    {
        $this->repeatModel->markAsInitialized();

        $this->assertFalse($this->repeatModel->isInitializeNeeded());
    }
}
