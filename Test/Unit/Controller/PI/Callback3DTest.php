<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\PI;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class Callback3DTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var /Ebizmarts\SagePaySuite\Controller\PI\Callback3D
     */
    protected $piCallback3DController;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirectMock;

    /**
     * @var  Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    protected function setUp()
    {
        $piModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\PI')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($piModelMock));

        $checkoutSessionMock = $this
            ->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->will($this->returnValue(self::TEST_VPSTXID));
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "PaRes" => "123456780"
            ]));

        $this->redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
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
            ->method('getRedirect')
            ->will($this->returnValue($this->redirectMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();

        $orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $orderFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->orderMock));

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $transactionFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));

        $pirestapiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')
            ->disableOriginalConstructor()
            ->getMock();
        $pirestapiMock->expects($this->any())
            ->method('submit3D')
            ->will($this->returnValue((object)[
                "status" => "Authenticated"
            ]));
        $pirestapiMock->expects($this->any())
            ->method('transactionDetails')
            ->will($this->returnValue((object)[
                "statusCode" => \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS,
                "statusDetail" => "OK Status",
                "3DSecure" => (object)[
                    "status" => "OK"
                ],
            ]));

        $checkoutHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Checkout')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->piCallback3DController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\Callback3D',
            [
                'context' => $contextMock,
                'config' => $configMock,
                'checkoutSession' => $checkoutSessionMock,
                'orderFactory' => $orderFactoryMock,
                'checkoutHelper' => $checkoutHelperMock,
                'pirestapi' => $pirestapiMock,
                'transactionFactory' => $transactionFactoryMock
            ]
        );
    }

    public function testExecuteSUCCESS()
    {
        $this->orderMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->_expectRedirect("checkout/onepage/success");
        $this->piCallback3DController->execute();
    }

    public function testExecuteERROR()
    {
        $this->orderMock->expects($this->any())
            ->method('load')
            ->willReturn(NULL);

        $this->_expectRedirect("checkout/cart");
        $this->piCallback3DController->execute();
    }

    /**
     * @param string $path
     */
    protected function _expectRedirect($path)
    {
        $this->redirectMock->expects($this->once())
            ->method('redirect')
            ->with($this->anything(), $path, []);
    }
}
