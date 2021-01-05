<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Paypal;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\ObjectLoader\OrderLoader;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;

class CallbackTest extends \PHPUnit_Framework_TestCase
{
    /** Sage Pay Transaction ID */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';
    const QUOTE_ID_ENCRYPTED = '0:2:Dwn8kCUk6nZU5B7b0Xn26uYQDeLUKBrD:S72utt9n585GrslZpDp+DRpW+8dpqiu/EiCHXwfEhS0=';
    const QUOTE_ID = 69;
    const ORDER_ID = 70;
    const ORDER_INCREMENT_ID = '000000001';
    const STATUS_ORDER = 'Processing';

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    /** @var OrderFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $orderFactoryMock;

    /** @var Config|\PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var Payment|\PHPUnit_Framework_MockObject_MockObject */
    private $paymentMock;

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

    /** @var \Ebizmarts\SagePaySuite\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteHelperMock;

    /** @var EncryptorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $encryptorMock;

    /** @var RecoverCart|\PHPUnit_Framework_MockObject_MockObject */
    private $recoverCartMock;

    /** @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    private $checkoutSessionMock;

    /** @var OrderLoader|\PHPUnit_Framework_MockObject_MockObject */
    private $orderLoaderMock;

    /** @var OrderUpdateOnCallback|\PHPUnit_Framework_MockObject_MockObject */
    private $updateOrderCallbackMock;

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

        $this->checkoutSessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->setMethods(
                [
                    'setLastQuoteId',
                    'setLastSuccessQuoteId',
                    'setLastOrderId',
                    'setLastRealOrderId',
                    'setLastOrderStatus',
                    'setData',
                    'clearHelperData'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
//        $this->checkoutSessionMock->expects($this->any())
//            ->method('getQuote')
//            ->will($this->returnValue($quoteMock));

        $this->responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with('quoteid')
            ->willReturn(self::QUOTE_ID_ENCRYPTED);

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
                    "AVSCV2" => "DATA NOT CHECKED",
                    "AddressResult" => "NOTPROVIDED",
                    "PostCodeResult" => "NOTPROVIDED",
                    "CV2Result" => "NOTPROVIDED"
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

        $this->suiteHelperMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Helper\Data")
            ->disableOriginalConstructor()
            ->setMethods(["removeCurlyBraces"])
            ->getMock();

        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->recoverCartMock = $this
            ->getMockBuilder(RecoverCart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderLoaderMock = $this
            ->getMockBuilder(OrderLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateOrderCallbackMock = $this
            ->getMockBuilder(OrderUpdateOnCallback::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper            = new ObjectManagerHelper($this);
        $this->paypalCallbackController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Paypal\Callback',
            [
                'context'             => $contextMock,
                'config'              => $this->configMock,
                'checkoutSession'     => $this->checkoutSessionMock,
                'checkoutHelper'      => $checkoutHelperMock,
                'postApi'             => $postApiMock,
                'transactionFactory'  => $transactionFactoryMock,
                'quoteFactory'        => $quoteFactoryMock,
                'orderFactory'        => $this->orderFactoryMock,
                'actionFactory'       => $closedForActionFactoryMock,
                'updateOrderCallback' => $this->updateOrderCallbackMock,
                'suiteHelper'         => $this->suiteHelperMock,
                'encryptor'           => $this->encryptorMock,
                'recoverCart'         => $this->recoverCartMock,
                'orderLoader'         => $this->orderLoaderMock
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
        $this->suiteHelperMock->expects($this->once())->method("removeCurlyBraces")->willReturn(self::TEST_VPSTXID);
        $this->configMock->method('getMode')->willReturn($mode);
        $this->configMock->method('getSagepayPaymentAction')->willReturn($paymentAction);
        $this->paymentMock->method('getLastTransId')->willReturn(self::TEST_VPSTXID);

        $this->orderMock->expects($this->once())->method('getId')->willReturn(self::ORDER_ID);
        $this->quoteMock->expects($this->exactly(3))->method('getId')->willReturn(self::QUOTE_ID);
        $this->orderMock->expects($this->once())->method('getIncrementId')->willReturn(self::ORDER_INCREMENT_ID);
        $this->orderMock->expects($this->once())->method('getStatus')->willReturn(self::STATUS_ORDER);
        $this->checkoutSessionMock->expects($this->once())->method("clearHelperData")->willReturn(null);
        $this->checkoutSessionMock
            ->expects($this->once())->method("setLastQuoteId")->with(self::QUOTE_ID);
        $this->checkoutSessionMock
            ->expects($this->once())->method("setLastSuccessQuoteId")->with(self::QUOTE_ID);
        $this->checkoutSessionMock
            ->expects($this->once())->method("setLastOrderId")->with(self::ORDER_ID);
        $this->checkoutSessionMock
            ->expects($this->once())->method("setLastRealOrderId")->with(self::ORDER_INCREMENT_ID);
        $this->checkoutSessionMock
            ->expects($this->once())->method("setLastOrderStatus")->with(self::STATUS_ORDER);
        $this->checkoutSessionMock
            ->expects($this->once())->method("setData")->with(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);

        $this->encryptorMock->expects($this->once())->method('decrypt')->with(self::QUOTE_ID_ENCRYPTED)
            ->willReturn(self::QUOTE_ID);

        $this->orderLoaderMock
            ->expects($this->once())
            ->method('loadOrderFromQuote')
            ->with($this->quoteMock)
            ->willReturn($this->orderMock);

        $this->updateOrderCallbackMock
            ->expects($this->once())
            ->method('setOrder')
            ->with($this->orderMock)
            ->willReturnSelf();
        $this->updateOrderCallbackMock
            ->expects($this->once())
            ->method('confirmPayment')
            ->with(self::TEST_VPSTXID)
            ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "Status" => "PAYPALOK",
                "StatusDetail" => "OK STATUS SUCCESS",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "3DSecureStatus" => "NOTCHECKED",
                "AVSCV2" => "DATA NOT CHECKED",
                "AddressResult" => "NOTPROVIDED",
                "PostCodeResult" => "NOTPROVIDED",
                "CV2Result" => "NOTPROVIDED"
            ]));

        $this->_expectRedirect("checkout/onepage/success");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERROR()
    {
        $this->encryptorMock
            ->expects($this->once())
            ->method('decrypt')
            ->with(self::QUOTE_ID_ENCRYPTED)
            ->willReturn(self::QUOTE_ID);
        $this->quoteMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::QUOTE_ID);
        $this->orderLoaderMock
            ->expects($this->once())
            ->method('loadOrderFromQuote')
            ->with($this->quoteMock)
            ->willReturn($this->orderMock);
        $this->orderMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::ORDER_ID);

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "Status" => "INVALID",
                "StatusDetail" => "INVALID STATUS"
            ]));

        $this->recoverCartMock
            ->expects($this->once())
            ->method('setShouldCancelOrder')
            ->with(true)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('setOrderId')
            ->with(self::ORDER_ID)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('execute');

        $this->_expectRedirect("checkout/cart");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERRORNoResponse()
    {
        $response = new \stdClass();

        $this->encryptorMock
            ->expects($this->once())
            ->method('decrypt')
            ->with(self::QUOTE_ID_ENCRYPTED)
            ->willReturn(self::QUOTE_ID);
        $this->quoteMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::QUOTE_ID);
        $this->orderLoaderMock
            ->expects($this->once())
            ->method('loadOrderFromQuote')
            ->with($this->quoteMock)
            ->willReturn($this->orderMock);
        $this->orderMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::ORDER_ID);

        $this->requestMock
            ->expects($this->once())
            ->method('getPost')
            ->willReturn($response);

        $this->recoverCartMock
            ->expects($this->once())
            ->method('setShouldCancelOrder')
            ->with(true)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('setOrderId')
            ->with(self::ORDER_ID)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('execute');

        $this->_expectRedirect("checkout/cart");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERRORInvalidQuote()
    {
        $this->quoteMock->method('getId')->willReturn(null);

        $this->encryptorMock->expects($this->once())->method('decrypt');

        $this->recoverCartMock
            ->expects($this->once())
            ->method('setShouldCancelOrder')
            ->with(true)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('setOrderId')
            ->with(null)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('execute');

        $this->_expectRedirect("checkout/cart");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERRORInvalidOrder()
    {
        $this->quoteMock->method('getId')->willReturn(69);
        $this->orderMock->method('getId')->willReturn(null);

        $this->encryptorMock->expects($this->once())->method('decrypt');

        $this->orderLoaderMock
            ->expects($this->once())
            ->method('loadOrderFromQuote')
            ->with($this->quoteMock)
            ->willReturn($this->orderMock);

        $this->recoverCartMock
            ->expects($this->once())
            ->method('setShouldCancelOrder')
            ->with(true)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('setOrderId')
            ->with(null)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('execute');

        $this->_expectRedirect("checkout/cart");
        $this->paypalCallbackController->execute();
    }

    public function testExecuteERRORInvalidTrnId()
    {
        $this->encryptorMock
            ->expects($this->once())
            ->method('decrypt')
            ->with(self::QUOTE_ID_ENCRYPTED)
            ->willReturn(self::QUOTE_ID);
        $this->quoteMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::QUOTE_ID);
        $this->orderLoaderMock
            ->expects($this->once())
            ->method('loadOrderFromQuote')
            ->with($this->quoteMock)
            ->willReturn($this->orderMock);
        $this->orderMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::ORDER_ID);

        $this->paymentMock->method('getLastTransId')->willReturn('notequal');

        $this->encryptorMock->expects($this->once())->method('decrypt');

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "Status" => "PAYPALOK",
                "StatusDetail" => "OK STATUS",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}"
            ]));

        $this->recoverCartMock
            ->expects($this->once())
            ->method('setShouldCancelOrder')
            ->with(true)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('setOrderId')
            ->with(self::ORDER_ID)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('execute');

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
