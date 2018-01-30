<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Form;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class FailureTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($requestMock));
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($responseMock));
        $contextMock->expects($this->any())
            ->method('getRedirect')
            ->will($this->returnValue($redirectMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));

        $formModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $formModelMock->expects($this->any())
            ->method('decodeSagePayResponse')
            ->will($this->returnValue([
                "Status" => "REJECTED",
                "StatusDetail" => "2000 : Invalid Card"
            ]));

        $quoteMock = $this->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock
            ->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $quoteMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1234);
        $quoteMock
            ->expects($this->once())
            ->method('setIsActive')
            ->with(1);
        $quoteMock
            ->expects($this->once())
            ->method('setReservedOrderId')
            ->with(null);
        $quoteMock
            ->expects($this->once())
            ->method('save');

        $quoteFactoryMock = $this->getMockBuilder('\Magento\Quote\Model\QuoteFactory')
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteMock);

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $orderMock->expects($this->once())
            ->method('cancel')
            ->willReturnSelf();
        $orderFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderMock);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $formFailureController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Failure',
            [
                'context'      => $contextMock,
                'formModel'    => $formModelMock,
                'quoteFactory' => $quoteFactoryMock,
                'orderFactory' => $orderFactoryMock

            ]
        );

        $messageManagerMock->expects($this->once())
            ->method('addError')
            ->with("REJECTED: Invalid Card");

        $redirectMock
            ->expects($this->once())
            ->method('redirect')
            ->with($this->anything(), "checkout/cart", []);

        $formFailureController->execute();
    }

    public function testExecuteException()
    {
        $responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($requestMock));
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($responseMock));
        $contextMock->expects($this->any())
            ->method('getRedirect')
            ->will($this->returnValue($redirectMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));

        $formModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $formModelMock->expects($this->any())
            ->method('decodeSagePayResponse')
            ->willReturn([]);

        $loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())->method('critical')->with(
            new \Magento\Framework\Exception\LocalizedException(__('Invalid response from Sage Pay'))
        );

        $objectManagerHelper = new ObjectManagerHelper($this);
        $formFailureController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Failure',
            [
                'context'   => $contextMock,
                'formModel' => $formModelMock,
                'logger'    => $loggerMock
            ]
        );

        $messageManagerMock
            ->expects($this->once())
            ->method('addError')
            ->with(__('Invalid response from Sage Pay'));

        $formFailureController->execute();
    }

    public function testExecuteOrderCancelException()
    {
        $responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($requestMock));
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($responseMock));
        $contextMock->expects($this->any())
            ->method('getRedirect')
            ->will($this->returnValue($redirectMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));

        $formModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $formModelMock->expects($this->any())
            ->method('decodeSagePayResponse')
            ->will($this->returnValue([
                "Status" => "REJECTED",
                "StatusDetail" => "2000 : Invalid Card"
            ]));

        $quoteMock = $this->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock
            ->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $quoteMock
            ->expects($this->never())
            ->method('getId');
        $quoteMock
            ->expects($this->never())
            ->method('setIsActive');
        $quoteMock
            ->expects($this->never())
            ->method('setReservedOrderId');
        $quoteMock
            ->expects($this->never())
            ->method('save');

        $quoteFactoryMock = $this->getMockBuilder('\Magento\Quote\Model\QuoteFactory')
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteMock);

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $orderMock->expects($this->once())
            ->method('cancel')
            ->willThrowException(new \Exception(new \Magento\Framework\Phrase("Can not cancel order.")));
        $orderFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderMock);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $formFailureController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Failure',
            [
                'context'      => $contextMock,
                'formModel'    => $formModelMock,
                'quoteFactory' => $quoteFactoryMock,
                'orderFactory' => $orderFactoryMock

            ]
        );

        $messageManagerMock->expects($this->once())
            ->method('addError')
            ->with("REJECTED: Invalid Card");

        $redirectMock
            ->expects($this->once())
            ->method('redirect')
            ->with($this->anything(), "checkout/cart", []);

        $formFailureController->execute();
    }
}
