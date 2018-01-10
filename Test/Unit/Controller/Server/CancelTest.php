<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class CancelTest extends \PHPUnit_Framework_TestCase
{
    const QUOTE_ID = 1234;
    const RESERVED_ORDER_ID = 5678;

    /**
     * @var \Ebizmarts\SagePaySuite\Controller\Server\Cancel
     */
    private $serverCancelController;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\RequestInterface')
            ->getMockForAbstractClass();
        $this->requestMock
            ->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(
                ["message"],
                ["quote"]
            )
            ->willReturnOnConsecutiveCalls("Error Message", self::QUOTE_ID);

        $this->responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $messageManagerMock->expects($this->once())
            ->method('addError')
            ->will($this->returnValue($this->requestMock));

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
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));
        $contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $cartMock = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartMock->expects($this->once())->method("setQuote");
        $cartMock->expects($this->once())->method("save");

        $objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManager\ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerMock->expects($this->once())->method("get")
            ->with("Magento\Checkout\Model\Cart")
            ->willReturn($cartMock);
        $contextMock->expects($this->once())
            ->method("getObjectManager")
            ->willReturn($objectManagerMock);

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
        ->disableOriginalConstructor()
        ->getMock();
        $quoteMock->expects($this->once())->method("getId")->willReturn(self::QUOTE_ID);
        $quoteMock->expects($this->once())->method("load")->willReturnSelf();
        $quoteMock->expects($this->once())->method("getReservedOrderId")->willReturn(self::RESERVED_ORDER_ID);

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method("loadByIncrementId")
            ->with(self::RESERVED_ORDER_ID)
        ->willReturnSelf();
        $orderMock->expects($this->once())->method("getId")->willReturn(self::RESERVED_ORDER_ID);
        $orderMock->expects($this->once())->method("getItemsCollection")
            ->willReturn([]);

        $orderFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();
        $orderFactoryMock->expects($this->once())
            ->method("create")
            ->willReturn($orderMock);

        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock->expects($this->once())->method("getQuote")
            ->willReturn($quoteMock);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->serverCancelController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Cancel',
            [
                'context'          => $contextMock,
                "quote"            => $quoteMock,
                "orderFactory"     => $orderFactoryMock,
                "_checkoutSession" => $checkoutSessionMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testExecute()
    {
        $this->_expectSetBody(
            '<script>window.top.location.href = "'
            . '";</script>'
        );

        $this->serverCancelController->execute();
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
