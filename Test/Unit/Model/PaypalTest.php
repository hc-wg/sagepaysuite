<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class PaypalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Paypal
     */
    protected $paypalModel;

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
        $this->paypalModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Paypal',
            [
                "config" => $this->configMock,
                "sharedApi" => $this->sharedApiMock,
                'suiteHelper' => $suiteHelperMock
            ]
        );
    }

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
            ->will($this->returnValue(\Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER));

        $this->sharedApiMock->expects($this->once())
            ->method('releaseTransaction')
            ->with(1,100);

        $this->paypalModel->capture($paymentMock,100);
    }

    public function testCaptureERROR()
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
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(2));
        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->with('paymentAction')
            ->will($this->returnValue(\Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE));

        $exception = new \Exception("Error in Authenticating");
        $this->sharedApiMock->expects($this->once())
            ->method('authorizeTransaction')
            ->with(2,100)
            ->willThrowException($exception);

        $response = "";
        try {
            $this->paypalModel->capture($paymentMock,100);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = $e->getMessage();
        }

        $this->assertEquals(
            'There was an error authorizing Sage Pay transaction 2: Error in Authenticating',
            $response
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

        $this->paypalModel->refund($paymentMock,100);

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
            $this->paypalModel->refund($paymentMock,100);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = $e->getMessage();
        }

        $this->assertEquals(
            'There was an error refunding Sage Pay transaction ' . self::TEST_VPSTXID . ': Error in Refunding',
            $response
        );

    }

    public function testGetConfigPaymentAction(){
        $this->configMock->expects($this->once())
            ->method('getPaymentAction');
        $this->paypalModel->getConfigPaymentAction();
    }
}