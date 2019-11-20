<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class CheckoutCartIndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Observer\CheckoutCartIndex
     */
    private $checkoutCartIndexObserver;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $checkoutSessionMock = $this
            ->getMockBuilder('\Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock->expects($this->once())
            ->method('getData')
            ->willReturn(1);
        $checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);
        $checkoutSessionMock->expects($this->once())
            ->method('replaceQuote');

        $quoteMock = $this->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn(12);
        $quoteFactoryMock = $this->getMockBuilder('\Magento\Quote\Model\QuoteFactory')
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteMock);

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $orderMock->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $orderMock->expects($this->once())
            ->method('getState')
            ->willReturn(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $orderMock->expects($this->once())
            ->method('cancel')
            ->willReturnSelf();

        $orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderMock));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->checkoutCartIndexObserver = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Observer\CheckoutCartIndex',
            [
                'orderFactory' => $orderFactoryMock,
                'quoteFactory' => $quoteFactoryMock,
                'checkoutSession' => $checkoutSessionMock,
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testExecute()
    {
        $this->checkoutCartIndexObserver->execute(new \Magento\Framework\Event\Observer);
    }
}
