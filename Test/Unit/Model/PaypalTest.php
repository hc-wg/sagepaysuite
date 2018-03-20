<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Exception\LocalizedException;

class PaypalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Paypal
     */
    private $paypalModel;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
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

        $this->paymentOpsMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Payment::class)->disableOriginalConstructor()->getMock();

        $this->paypalModel = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Paypal::class)
            ->setConstructorArgs(
                [
                    'context' => $this->getMockBuilder(\Magento\Framework\Model\Context::class)->disableOriginalConstructor()->getMock(),
                    'registry' => $this->getMockBuilder(\Magento\Framework\Registry::class)->disableOriginalConstructor()->getMock(),
                    'extensionFactory' => $this->getMockBuilder('\Magento\Framework\Api\ExtensionAttributesFactory')->disableOriginalConstructor()->getMock(),
                    'customAttributeFactory' => $this->getMockBuilder('\Magento\Framework\Api\AttributeValueFactory')->disableOriginalConstructor()->getMock(),
                    'paymentOps' => $this->paymentOpsMock,
                    'paymentData' => $this->getMockBuilder(\Magento\Payment\Helper\Data::class)->disableOriginalConstructor()->getMock(),
                    'scopeConfig' => $this->getMockBuilder('\Magento\Framework\App\Config\ScopeConfigInterface')->disableOriginalConstructor()->getMock(),
                    'logger' => $this->getMockBuilder(\Magento\Payment\Model\Method\Logger::class)->disableOriginalConstructor()->getMock(),
                    'config' => $this->configMock,
                    'resource' => null,
                    'resourceCollection' => null,
                    'data' => [],
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
            ->willReturn(1);
        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->with('paymentAction')
            ->willReturn(Config::ACTION_DEFER);

        $this->paymentOpsMock->expects($this->once())->method('capture')->with($paymentMock, 100);

        $this->paypalModel
            ->setMethods(['refund'])
            ->getMock()->capture($paymentMock, 100);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage There was an error capturing Sage Pay transaction 11: 22
     */
    public function testCaptureError()
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
            ->will($this->returnValue(Config::ACTION_AUTHENTICATE));

        $this->paymentOpsMock->expects($this->once())->method('capture')->with($paymentMock, 100)
        ->willThrowException(
            new LocalizedException(__('There was an error capturing Sage Pay transaction 11: 22'))
        );

        $this->paypalModel
            ->setMethods(['refund'])
            ->getMock()->capture($paymentMock, 100);
    }

    public function testRefund()
    {
        $paymentMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')->disableOriginalConstructor()->getMock();

        $this->paymentOpsMock->expects($this->once())->method('refund')->with($paymentMock, 100);

        $this->assertInstanceOf(\Ebizmarts\SagePaySuite\Model\Paypal::class, $this->paypalModel
            ->setMethods(['capture'])
            ->getMock()->refund($paymentMock, 100));
    }

    /**
     * @expectedExceptionMessage There was an error refunding Sage Pay transaction
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testRefundError()
    {
        $paymentMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')->disableOriginalConstructor()->getMock();

        $this->paymentOpsMock->expects($this->once())->method('refund')->with($paymentMock, 100)
            ->willThrowException(
                new LocalizedException(__('There was an error refunding Sage Pay transaction '.self::TEST_VPSTXID))
            );

        $this->paypalModel
            ->setMethods(['capture'])
            ->getMock()
            ->refund($paymentMock, 100);
    }

    public function testGetConfigPaymentAction()
    {
        $this->configMock->expects($this->once())->method('getPaymentAction');

        $this->paypalModel->setMethods(['capture'])->getMock()->getConfigPaymentAction();
    }
}
