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
    private $piCallback3DController;

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
     * @var \Magento\Framework\App\Response\RedirectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $redirectMock;

    /**
     * @var  Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    // @codingStandardsIgnoreStart
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

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
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
        $contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

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
            ->setMethods(['create'])
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
            ->setMethods(['create'])
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
    // @codingStandardsIgnoreEnd

    public function testExecuteSUCCESS()
    {
        $this->orderMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->_expectSetBody(
            '<script>window.top.location.href = "'
            . $this->urlBuilderMock->getUrl('checkout/onepage/success', ['_secure' => true])
            . '";</script>'
        );

        $this->piCallback3DController->execute();
    }

    public function testExecuteERROR()
    {
        $this->orderMock->expects($this->any())
            ->method('load')
            ->willReturn(null);

        $this->_expectSetBody(
            '<script>window.top.location.href = "'
            . $this->urlBuilderMock->getUrl('checkout/cart', ['_secure' => true])
            . '";</script>'
        );

        $this->piCallback3DController->execute();
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
