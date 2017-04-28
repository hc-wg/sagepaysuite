<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Paypal;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class CallbackTest extends \PHPUnit_Framework_TestCase
{
    private $quoteMock;
    private $orderFactoryMock;
    private $configMock;
    private $paymentMock;

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var /Ebizmarts\SagePaySuite\Controller\Paypal\Callback
     */
    private $paypalCallbackController;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseMock;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $redirectMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->paymentMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')->disableOriginalConstructor()->getMock();
        $this->paymentMock->method('getMethodInstance')->willReturnSelf();

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getGrandTotal')
            ->will($this->returnValue(100));
        $quoteMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));

        $checkoutSessionMock = $this
            ->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quoteMock));

        $this->responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));

        $this->orderMock->method('place')->willReturnSelf();

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

        $postApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Post')
            ->disableOriginalConstructor()
            ->getMock();
        $postApiMock->expects($this->any())
            ->method('sendPost')
            ->will($this->returnValue([
                "data" => [
                    "VPSTxId"        => "{" . self::TEST_VPSTXID . "}",
                    "StatusDetail"   => "OK STATUS",
                    "3DSecureStatus" => "NOTCHECKED",
                ]
            ]));

        $checkoutHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Checkout')
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue($this->orderMock));

        $this->quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->disableOriginalConstructor()->getMock();

        $quoteFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\QuoteFactory::class)
            ->setMethods(['create', 'load'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteFactoryMock->method('create')->willReturnSelf();
        $quoteFactoryMock->method('load')->willReturn($this->quoteMock);

        $this->orderFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)->setMethods(['create', 'loadByIncrementId'])->disableOriginalConstructor()->getMock();
        $this->orderFactoryMock->method('create')->willReturnSelf();
        $this->orderFactoryMock->method('loadByIncrementId')->willReturn($this->orderMock);

        $closedForActionFactoryMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $closedForActionMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config\ClosedForAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $closedForActionFactoryMock->method('create')->willReturn($closedForActionMock);

        $objectManagerHelper            = new ObjectManagerHelper($this);
        $this->paypalCallbackController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Paypal\Callback',
            [
                'context'            => $contextMock,
                'config'             => $this->configMock,
                'checkoutSession'    => $checkoutSessionMock,
                'checkoutHelper'     => $checkoutHelperMock,
                'postApi'            => $postApiMock,
                'transactionFactory' => $transactionFactoryMock,
                'quoteFactory'       => $quoteFactoryMock,
                'orderFactory'       => $this->orderFactoryMock,
                "actionFactory"      => $closedForActionFactoryMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function modeProvider()
    {
        return [
            'test live payment' => ['live', 'PAYMENT'],
            'test live deferred' => ['live', 'AUTHENTICATE'],
            'test deferred' => ['test', 'DEFERRED'],
            'test capture default' => ['test', null]
        ];
    }

    /**
     * @dataProvider modeProvider
     */
    public function testExecuteSUCCESS($mode, $paymentAction)
    {
        $this->configMock->method('getMode')->willReturn($mode);
        $this->configMock->method('getSagepayPaymentAction')->willReturn($paymentAction);
        $this->paymentMock->method('getLastTransId')->willReturn(self::TEST_VPSTXID);
        $this->orderMock->expects($this->exactly(2))->method('getId')->willReturn(70);
        $this->quoteMock->expects($this->exactly(3))->method('getId')->willReturn(69);

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "Status" => "PAYPALOK",
                "StatusDetail" => "OK STATUS",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}"
            ]));

        $this->_expectRedirect("checkout/onepage/success");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERROR()
    {
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "Status" => "INVALID",
                "StatusDetail" => "INVALID STATUS"
            ]));

        $this->_expectRedirect("checkout/cart");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERRORNoResponse()
    {
        $response = new \stdClass();

        $this->requestMock
            ->expects($this->once())
            ->method('getPost')
            ->willReturn($response);

        $this->_expectRedirect("checkout/cart");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERRORInvalidQuote()
    {
        $this->quoteMock->method('getId')->willReturn(null);

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "Status" => "PAYPALOK",
                "StatusDetail" => "OK STATUS",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}"
            ]));

        $this->_expectRedirect("checkout/cart");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERRORInvalidOrder()
    {
        $this->quoteMock->method('getId')->willReturn(69);
        $this->orderMock->method('getId')->willReturn(null);

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "Status" => "PAYPALOK",
                "StatusDetail" => "OK STATUS",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}"
            ]));

        $this->_expectRedirect("checkout/cart");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERRORInvalidTrnId()
    {
        $this->quoteMock->method('getId')->willReturn(69);
        $this->orderMock->method('getId')->willReturn(70);
        $this->paymentMock->method('getLastTransId')->willReturn('notequal');

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "Status" => "PAYPALOK",
                "StatusDetail" => "OK STATUS",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}"
            ]));

        $this->_expectRedirect("checkout/cart");
        $this->paypalCallbackController->execute();
    }

    /**
     * @param string $path
     */
    private function _expectRedirect($path)
    {
        $this->redirectMock->expects($this->once())
            ->method('redirect')
            ->with($this->anything(), $path, []);
    }
}
