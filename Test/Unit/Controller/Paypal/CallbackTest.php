<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Paypal;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class CallbackTest extends \PHPUnit\Framework\TestCase
{
    private $quoteMock;
    private $orderFactoryMock;
    private $configMock;
    private $paymentMock;

    /** @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    private $checkoutSessionMock;

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Controller\Paypal\Callback
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

    /** @var \Ebizmarts\SagePaySuite\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteHelperMock;
    private $encryptorMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->paymentMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->setMethods(["getMethodInstance", "getLastTransId", "save"])
            ->disableOriginalConstructor()->getMock();
        $this->paymentMock->method('getMethodInstance')->willReturnSelf();

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->setMethods(["getGrandTotal", "getPayment"])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getGrandTotal')
            ->will($this->returnValue(100));
        $quoteMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));

        $this->checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->setMethods(
                [
                    "getQuote",
                    "clearHelperData",
                    "setLastQuoteId",
                    "setLastSuccessQuoteId",
                    "setLastOrderId",
                    "setLastRealOrderId",
                    "setLastOrderStatus",
                    "setData"
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quoteMock));

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock->expects($this->any())->method('getParam')->with('quoteid')->willReturn(69);

        $this->redirectMock = $this->getMockBuilder(\Magento\Store\App\Response\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->setMethods(["getRequest","getResponse", "getRedirect", "getMessageManager"])
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
            ->setMethods(["getPayment", "place", "getId"])
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
            ->setMethods(["sendPost"])
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
            ->setMethods(["placeOrder"])
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue($this->orderMock));

        $this->quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(["getId"])
            ->getMock();

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

        $this->suiteHelperMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Helper\Data")
            ->disableOriginalConstructor()
            ->setMethods(["methodCodeIsSagePay"])
            ->getMock();

        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper            = new ObjectManagerHelper($this);
        $this->paypalCallbackController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Paypal\Callback',
            [
                'context'            => $contextMock,
                'config'             => $this->configMock,
                'checkoutSession'    => $this->checkoutSessionMock,
                'checkoutHelper'     => $checkoutHelperMock,
                'postApi'            => $postApiMock,
                'transactionFactory' => $transactionFactoryMock,
                'quoteFactory'       => $quoteFactoryMock,
                'orderFactory'       => $this->orderFactoryMock,
                "actionFactory"      => $closedForActionFactoryMock,
                "suiteHelper"        => $this->suiteHelperMock,
                "encryptor"          => $this->encryptorMock
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
        $this->checkoutSessionMock->expects($this->once())->method("clearHelperData")->willReturn(null);
        $this->checkoutSessionMock
            ->expects($this->once())->method("setLastQuoteId")->with(69);
        $this->checkoutSessionMock
            ->expects($this->once())->method("setLastSuccessQuoteId")->with(69);
        $this->checkoutSessionMock
            ->expects($this->once())->method("setLastOrderId")->with(70);

        $this->encryptorMock->expects($this->once())->method('decrypt')->with(69)
        ->willReturn('0:2:Dwn8kCUk6nZU5B7b0Xn26uYQDeLUKBrD:S72utt9n585GrslZpDp+DRpW+8dpqiu/EiCHXwfEhS0=');

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "Status" => "PAYPALOK",
                "StatusDetail" => "OK STATUS SUCCESS",
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

        $this->encryptorMock->expects($this->once())->method('decrypt');

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

        $this->encryptorMock->expects($this->once())->method('decrypt');

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
        $this->paymentMock->method('save')->willReturnSelf();

        $this->encryptorMock->expects($this->once())->method('decrypt');

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
