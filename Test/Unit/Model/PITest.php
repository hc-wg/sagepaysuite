<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class PITest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Model\PI
     */
    protected $piModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sharedApiMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

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

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->piModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config" => $this->configMock,
                'suiteHelper' => $suiteHelperMock,
                "sharedApi" => $this->sharedApiMock
            ]
        );
    }

//    public function testAssignData()
//    {
//        $paymentMock = $this
//            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
//            ->disableOriginalConstructor()
//            ->getMock();
//        $paymentMock->expects($this->once())
//            ->method('setCcType')
//            ->willReturnSelf();
//        $paymentMock->expects($this->once())
//            ->method('setCcOwner')
//            ->willReturnSelf();
//        $paymentMock->expects($this->once())
//            ->method('setCcLast4')
//            ->willReturnSelf();
//        $paymentMock->expects($this->once())
//            ->method('setCcNumber')
//            ->willReturnSelf();
//        $paymentMock->expects($this->once())
//            ->method('setCcCid')
//            ->willReturnSelf();
//        $paymentMock->expects($this->once())
//            ->method('setCcExpMonth')
//            ->willReturnSelf();
//        $paymentMock->expects($this->once())
//            ->method('setCcExpYear')
//            ->willReturnSelf();
//        $paymentMock->expects($this->once())
//            ->method('setCcSsIssue')
//            ->willReturnSelf();
//        $paymentMock->expects($this->once())
//            ->method('setCcSsStartMonth')
//            ->willReturnSelf();
//        $paymentMock->expects($this->once())
//            ->method('setCcSsStartYear')
//            ->willReturnSelf();
//
//        $dataMock = $this
//            ->getMockBuilder('Magento\Framework\DataObject')
//            ->disableOriginalConstructor()
//            ->getMock();
//        $dataMock->expects($this->any())
//            ->method('getData');
//
//        $paymentMock->expects($this->exactly(3))
//            ->method('setAdditionalInformation');
//
//        $this->piModel->setInfoInstance($paymentMock);
//        $this->piModel->assignData($dataMock);
//    }

    public function testMarkAsInitialized()
    {
        $this->piModel->markAsInitialized();
        $this->assertEquals(
            false,
            $this->piModel->isInitializeNeeded()
        );
    }

    public function testRefund()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->will($this->returnValue(1000001));

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));
        $paymentMock->expects($this->once())
            ->method('setIsTransactionClosed')
            ->with(1);
        $paymentMock->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with(1);

        $this->sharedApiMock->expects($this->once())
            ->method('refundTransaction')
            ->with(self::TEST_VPSTXID,100,1000001);

        $this->piModel->refund($paymentMock,100);

    }

    public function testRefundERROR()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->will($this->returnValue(1000001));

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));

        $exception = new \Exception("Error in Refunding");
        $this->sharedApiMock->expects($this->once())
            ->method('refundTransaction')
            ->with(self::TEST_VPSTXID,100,1000001)
            ->willThrowException($exception);

        $response = "";
        try {
            $this->piModel->refund($paymentMock,100);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = $e->getMessage();
        }

        $this->assertEquals(
            'There was an error refunding Sage Pay transaction ' . self::TEST_VPSTXID . ': Error in Refunding',
            $response
        );

    }

    public function testCancel()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));

        $this->sharedApiMock->expects($this->once())
            ->method('voidTransaction')
            ->with(self::TEST_VPSTXID);

        $this->piModel->cancel($paymentMock);
    }

    public function testCancelERROR()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));

        $exception = new \Exception("Error in Voiding");
        $this->sharedApiMock->expects($this->once())
            ->method('voidTransaction')
            ->with(self::TEST_VPSTXID)
            ->willThrowException($exception);

        $response = "";
        try {
            $this->piModel->cancel($paymentMock);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = $e->getMessage();
        }

        $this->assertEquals(
            'Unable to VOID Sage Pay transaction ' . self::TEST_VPSTXID . ': Error in Voiding',
            $response
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

        $this->piModel->setInfoInstance($paymentMock);
        $this->piModel->initialize("",$stateMock);
    }

    public function testGetConfigPaymentAction(){
        $this->configMock->expects($this->once())
            ->method('getPaymentAction');
        $this->piModel->getConfigPaymentAction();
    }
}
