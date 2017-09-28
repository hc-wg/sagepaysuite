<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class SuccessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Delete
     */
    private $serverSuccessController;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseMock;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    public function testExecute()
    {
        $checkoutSessionMock = $this
            ->getMockBuilder('\Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\RequestInterface')
            ->getMockForAbstractClass();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $quoteMock = $this->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
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
            ->method('loadByIncrementId')
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
        $this->serverSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Success',
            [
                'context'         => $contextMock,
                'orderFactory'    => $orderFactoryMock,
                'quoteFactory'    => $quoteFactoryMock,
                'checkoutSession' => $checkoutSessionMock,
            ]
        );

        $this->_expectSetBody(
            '<script>window.top.location.href = "'
            . $this->urlBuilderMock->getUrl('checkout/onepage/success', ['_secure' => true])
            . '";</script>'
        );

        $this->serverSuccessController->execute();
    }

    public function testException()
    {
        $messageManagerMock = $this->getMockBuilder('\Magento\Framework\Message\ManagerInterface')
        ->disableOriginalConstructor()
        ->getMock();

        $urlMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this
                ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock());
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($messageManagerMock);
        $contextMock->expects($this->any())
            ->method('getUrl')
            ->willReturn($urlMock);

        $expectedException = new \Exception("Could not load quote.");
        $quoteFactoryMock = $this
            ->getMockBuilder(\Magento\Quote\Model\QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willThrowException($expectedException);

        $loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    "critical",
                    "emergency",
                    "alert",
                    "error",
                    "warning",
                    "info",
                    "debug",
                    "log",
                    "notice"
                ]
            )
            ->getMock();
        $loggerMock->expects($this->once())->method('critical')->with($expectedException);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $serverSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Success',
            [
                'context'      => $contextMock,
                'quoteFactory' => $quoteFactoryMock,
                'logger'       => $loggerMock,
            ]
        );

        $serverSuccessController->execute();
    }

    /**
     * @param $body
     */
    private function _expectSetBody($body)
    {
        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($body);
    }
}
