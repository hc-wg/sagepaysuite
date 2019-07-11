<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Form;

use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use \Magento\Framework\Exception\AlreadyExistsException;

class SuccessTest extends \PHPUnit\Framework\TestCase
{
    private $quoteFactoryMock;
    private $orderFactoryMock;

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Controller\Form\Success
     */
    private $formSuccessController;

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

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutHelperMock;

    private $contextMock;

    /** @var \Ebizmarts\SagePaySuite\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    private $suiteHelperMock;

    public function setUp()
    {
        $this->suiteHelperMock = $this->getMockBuilder(Data::class)
            ->setMethods(['verify'])
            ->disableOriginalConstructor()->getMock();
    }

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
    public function testExecuteSuccess($mode, $paymentAction)
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->method('getAdditionalInformation')
            ->willReturnOnConsecutiveCalls("100000001-2016-12-12-12346789", false);

        $quoteMock = $this->makeQuoteMock($paymentMock);

        $checkoutSessionMock = $this->makeCheckoutSessionMock($quoteMock);
        $checkoutSessionMock->expects($this->once())->method('start')->willReturnSelf();

        $this->makeResponseMock();

        $this->makeRequestMock();

        $this->makeRedirectMock();

        $messageManagerMock = $this->makeMessageManagerMock();

        $contextMock = $this->makeContextMock($messageManagerMock);

        $this->makeOrderMock($paymentMock);

        $this->checkoutHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Checkout')
            ->disableOriginalConstructor()
            ->getMock();

        $formModelMock = $this->makeFormModelMock('OK');

        $quoteMock1 = $this->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock1->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->makeQuoteFactoryMock($quoteMock1);

        $this->makeOrderFactoryMock();

        $this->orderMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(4);

        $this->checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue($this->orderMock));

        $this->_expectRedirect("checkout/onepage/success");

        $updateOrderCallbackMock = $this->getMockBuilder(OrderUpdateOnCallback::class)
            ->disableOriginalConstructor()->getMock();
        $updateOrderCallbackMock->expects($this->once())->method('setOrder')->with($this->orderMock);
        $updateOrderCallbackMock->expects($this->once())->method('confirmPayment')->with(self::TEST_VPSTXID);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->formSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Success',
            [
                'context'            => $contextMock,
                'checkoutSession'    => $checkoutSessionMock,
                'checkoutHelper'     => $this->checkoutHelperMock,
                'formModel'          => $formModelMock,
                'quoteFactory'       => $this->quoteFactoryMock,
                'orderFactory'       => $this->orderFactoryMock,
                'suiteHelper'        => $this->suiteHelperMock,
                'updateOrderCallback' => $updateOrderCallbackMock
            ]
        );

        $this->formSuccessController->execute();
    }

    /**
     * @dataProvider modeProvider
     */
    public function testExecuteSuccessRetry($mode, $paymentAction)
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->method('getAdditionalInformation')
            ->willReturnOnConsecutiveCalls("100000001-2016-12-12-12346789", false);

        $quoteMock = $this->makeQuoteMock($paymentMock);

        $checkoutSessionMock = $this->makeCheckoutSessionMock($quoteMock);
        $checkoutSessionMock->expects($this->once())->method('start')->willReturnSelf();

        $this->makeResponseMock();

        $this->makeRequestMock();

        $this->makeRedirectMock();

        $messageManagerMock = $this->makeMessageManagerMock();

        $contextMock = $this->makeContextMock($messageManagerMock);

        $this->makeOrderMock($paymentMock);

        $this->checkoutHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Checkout')
            ->disableOriginalConstructor()
            ->getMock();

        $formModelMock = $this->makeFormModelMock('OK');

        $quoteMock1 = $this->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock1->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->makeQuoteFactoryMock($quoteMock1);

        $this->makeOrderFactoryMock();

        $this->orderMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(4);

        $this->checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue($this->orderMock));

        $this->_expectRedirect("checkout/onepage/success");

        $updateOrderCallbackMock = $this->getMockBuilder(OrderUpdateOnCallback::class)
            ->disableOriginalConstructor()->getMock();
        $updateOrderCallbackMock->expects($this->once())->method('setOrder')->with($this->orderMock);
        $updateOrderCallbackMock->expects($this->once())->method('confirmPayment')->with(self::TEST_VPSTXID)
        ->willThrowException(new AlreadyExistsException(__('Transaction already exists.')));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->formSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Success',
            [
                'context'            => $contextMock,
                'checkoutSession'    => $checkoutSessionMock,
                'checkoutHelper'     => $this->checkoutHelperMock,
                'formModel'          => $formModelMock,
                'quoteFactory'       => $this->quoteFactoryMock,
                'orderFactory'       => $this->orderFactoryMock,
                'suiteHelper'        => $this->suiteHelperMock,
                'updateOrderCallback' => $updateOrderCallbackMock
            ]
        );

        $this->formSuccessController->execute();
    }

    /**
     * @dataProvider modeProvider
     */
    public function testExecuteSuccessPendingStatus($mode, $paymentAction)
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->method('getAdditionalInformation')
            ->willReturnOnConsecutiveCalls("100000001-2016-12-12-12346789", true);

        $quoteMock = $this->makeQuoteMock($paymentMock);

        $checkoutSessionMock = $this->makeCheckoutSessionMock($quoteMock);
        $checkoutSessionMock->expects($this->once())->method('start')->willReturnSelf();

        $this->makeResponseMock();

        $this->makeRequestMock();

        $this->makeRedirectMock();

        $messageManagerMock = $this->makeMessageManagerMock();

        $contextMock = $this->makeContextMock($messageManagerMock);

        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->never())->method('place');

        $transactionMock = $this->makeTransactionMock();

        $transactionFactoryMock = $this->makeTransactionFactoryMock($transactionMock);

        $this->checkoutHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Checkout')
            ->disableOriginalConstructor()
            ->getMock();

        $formModelMock = $this->makeFormModelMock('PENDING');

        $paymentMock->expects($this->never())->method('getMethodInstance');

        $quoteMock1 = $this->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock1->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactoryMock = $this->getMockBuilder('\Magento\Quote\Model\QuoteFactory')
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteMock1);

        $this->makeOrderFactoryMock();

        $orderSenderMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Email\Sender\OrderSender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderSenderMock->expects($this->once())->method('send');

        $this->orderMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(4);

        $invoiceCollectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Invoice\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceCollectionMock->expects($this->never())->method('setDataToAll');
        $this->orderMock
            ->expects($this->never())
            ->method('getInvoiceCollection');

        $this->checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue($this->orderMock));

        $this->_expectRedirect("checkout/onepage/success");

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->formSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Success',
            [
                'context'            => $contextMock,
                'checkoutSession'    => $checkoutSessionMock,
                'checkoutHelper'     => $this->checkoutHelperMock,
                'transactionFactory' => $transactionFactoryMock,
                'formModel'          => $formModelMock,
                'quoteFactory'       => $this->quoteFactoryMock,
                'orderFactory'       => $this->orderFactoryMock,
                'orderSender'        => $orderSenderMock,
                'suiteHelper'        => $this->suiteHelperMock
            ]
        );

        $this->formSuccessController->execute();
    }

    public function testExecuteError()
    {
        $quoteMock1 = $this->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock1->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactoryMock = $this->getMockBuilder('\Magento\Quote\Model\QuoteFactory')
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteMock1);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock = $this->makeQuoteMock($paymentMock);

        $invoiceCollectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Invoice\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->makeOrderMock($paymentMock);
        $this->orderMock->method('getId')->willReturn(null);

        $this->makeOrderFactoryMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock = $this->makeQuoteMock($paymentMock);

        $checkoutSessionMock = $this->makeCheckoutSessionMock($quoteMock);

        $this->makeResponseMock();

        $this->makeRequestMock();

        $this->makeRedirectMock();

        $messageManagerMock = $this->makeMessageManagerMock();
        $messageManagerMock->expects($this->once())->method('addError')->with(
            'Your payment was successful but the order was NOT created, please contact us: Order not available.'
        );

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getRedirect')
            ->will($this->returnValue($this->redirectMock));
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));

        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $transactionMock = $this->makeTransactionMock();

        $transactionFactoryMock = $this->makeTransactionFactoryMock($transactionMock);

        $this->checkoutHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Checkout')
            ->disableOriginalConstructor()
            ->getMock();

        $formModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $formModelMock->expects($this->any())
            ->method('decodeSagePayResponse')
            ->will($this->returnValue([
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "StatusDetail" => "OK_STATUS_DETAIL",
                "VendorTxCode" => "a100000001-2016-12-12-12346789",
                "3DSecureStatus" => "OK",
                "Status" => "OK"
            ]));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $formSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Success',
            [
                'context'            => $this->contextMock,
                'checkoutSession'    => $checkoutSessionMock,
                'checkoutHelper'     => $this->checkoutHelperMock,
                'transactionFactory' => $transactionFactoryMock,
                'formModel'          => $formModelMock,
                'quoteFactory'       => $this->quoteFactoryMock,
                'orderFactory'       => $this->orderFactoryMock
            ]
        );

        $this->checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue(null));

        $this->_expectRedirect("checkout/cart");
        $formSuccessController->execute();
    }

    public function testCryptDoesNotContainVpsTxId()
    {
        $this->makeResponseMock();

        $this->makeRequestMock();

        $this->makeRedirectMock();

        $messageManagerMock = $this->makeMessageManagerMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getRedirect')
            ->will($this->returnValue($this->redirectMock));
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));

        $invalidMessage  = 'Your payment was successful but the order was NOT created, please contact us: ';
        $invalidMessage .= 'Invalid response from Sage Pay.';
        $messageManagerMock->expects($this->once())->method('addError')->with($invalidMessage);

        $expectedException = new \Magento\Framework\Exception\LocalizedException(__('Invalid response from Sage Pay.'));

        $loggerMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class)
            ->setMethods(
                [
                    'logException'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())->method('logException')->with($expectedException);

        $formModelMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $formModelMock->expects($this->any())
            ->method('decodeSagePayResponse')
            ->will($this->returnValue([
                "CardType"       => "VISA",
                "Last4Digits"    => "0006",
                "StatusDetail"   => "OK_STATUS_DETAIL",
                "VendorTxCode"   => "a100000001-2016-12-12-12346789",
                "3DSecureStatus" => "OK",
                "Status"         => "OK"
            ]));

        $objectManagerHelper   = new ObjectManagerHelper($this);
        $formSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Success',
            [
                'context'   => $this->contextMock,
                'formModel' => $formModelMock,
                'suiteLogger'    => $loggerMock
            ]
        );

        $this->_expectRedirect("checkout/cart");
        $formSuccessController->execute();
    }

    public function testVpsTxIdDontMatch()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->expects($this->once())
            ->method('getAdditionalInformation')
            ->with("vendorTxCode")
            ->willReturn("100000001-2016-12-12-12346789");

        $quoteMock = $this->makeQuoteMock($paymentMock);

        $responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $invalidMessage  = 'Your payment was successful but the order was NOT created, please contact us: ';
        $invalidMessage .= 'Invalid transaction id.';

        $messageManagerMock = $this->getMockBuilder(\Magento\Framework\Message\Manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $messageManagerMock->expects($this->once())->method('addError')->with($invalidMessage);

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

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $orderMock->expects($this->once())
            ->method('loadByIncrementId')
            ->willReturnSelf();

        $expectedException = new \Magento\Framework\Validator\Exception(__('Invalid transaction id.'));

        $loggerMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class)
            ->setMethods(
                [
                    'logException',
                    'sageLog'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())->method('logException')->with($expectedException);

        $formModelMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $formModelMock->expects($this->any())
            ->method('decodeSagePayResponse')
            ->willReturn(
                [
                    "VPSTxId"        => "{" . self::TEST_VPSTXID . "}",
                    "CardType"       => "VISA",
                    "Last4Digits"    => "0006",
                    "StatusDetail"   => "OK_STATUS_DETAIL",
                    "VendorTxCode"   => "not_match_trn_id",
                    "3DSecureStatus" => "OK",
                    "Status"         => "OK",
                    "ExpiryDate"     => "0419",
                ]
            );

        $quoteMock1 = $this->getMockBuilder('\Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock1->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $quoteFactoryMock = $this->getMockBuilder('\Magento\Quote\Model\QuoteFactory')
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteMock1);

        $orderFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();

        $orderMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn(4);

        $orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderMock);

        $redirectMock
            ->expects($this->once())
            ->method('redirect')
            ->with($this->anything(), "checkout/cart", []);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->formSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Success',
            [
                'context'            => $contextMock,
                'formModel'          => $formModelMock,
                'quoteFactory'       => $quoteFactoryMock,
                'orderFactory'       => $orderFactoryMock,
                'suiteLogger'        => $loggerMock
            ]
        );

        $this->formSuccessController->execute();
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeFormModelMock($status)
    {
        $formModelMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Form')->disableOriginalConstructor()->getMock();
        $formModelMock->expects($this->any())->method('decodeSagePayResponse')->willReturn([
                    "VPSTxId"        => "{".self::TEST_VPSTXID."}",
                    "CardType"       => "VISA",
                    "Last4Digits"    => "0006",
                    "StatusDetail"   => "OK_STATUS_DETAIL",
                    "VendorTxCode"   => "100000001-2016-12-12-12346789",
                    "3DSecureStatus" => "OK",
                    "Status"         => $status,
                    "ExpiryDate"     => "0419",
                ]);

        return $formModelMock;
    }

    /**
     * @param $paymentMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeQuoteMock($paymentMock)
    {
        $quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $quoteMock->expects($this->any())->method('getPayment')->will($this->returnValue($paymentMock));

        return $quoteMock;
    }

    /**
     * @param $quoteMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeCheckoutSessionMock($quoteMock)
    {
        $checkoutSessionMock = $this->getMockBuilder('Magento\Checkout\Model\Session')->disableOriginalConstructor()->getMock();
        $checkoutSessionMock->expects($this->any())->method('getQuote')->will($this->returnValue($quoteMock));

        return $checkoutSessionMock;
    }

    private function makeResponseMock()
    {
        $this->responseMock = $this->getMockBuilder(
            'Magento\Framework\App\Response\Http',
            [],
            [],
            '',
            false
        )
            ->disableOriginalConstructor()->getMock();
    }

    private function makeRequestMock()
    {
        $this->requestMock = $this->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')->disableOriginalConstructor()->getMock();
    }

    private function makeRedirectMock()
    {
        $this->redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');
    }

    /**
     * @param $messageManagerMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeContextMock($messageManagerMock)
    {
        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')->disableOriginalConstructor()->getMock();
        $contextMock->expects($this->any())->method('getRequest')->will($this->returnValue($this->requestMock));
        $contextMock->expects($this->any())->method('getResponse')->will($this->returnValue($this->responseMock));
        $contextMock->expects($this->any())->method('getRedirect')->will($this->returnValue($this->redirectMock));
        $contextMock->expects($this->any())->method('getMessageManager')->will($this->returnValue($messageManagerMock));

        return $contextMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeMessageManagerMock()
    {
        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')->disableOriginalConstructor()->getMock();

        return $messageManagerMock;
    }

    /**
     * @param $paymentMock
     */
    private function makeOrderMock($paymentMock)
    {
        $this->orderMock = $this->getMockBuilder('Magento\Sales\Model\Order')->disableOriginalConstructor()->getMock();
        $this->orderMock->expects($this->any())->method('getPayment')->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->once())->method('loadByIncrementId')->willReturnSelf();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeTransactionMock()
    {
        $transactionMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')->disableOriginalConstructor()->getMock();

        return $transactionMock;
    }

    /**
     * @param $transactionMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeTransactionFactoryMock($transactionMock)
    {
        $transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $transactionFactoryMock->expects($this->any())->method('create')->will($this->returnValue($transactionMock));

        return $transactionFactoryMock;
    }

    private function makeOrderFactoryMock()
    {
        $this->orderFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)->disableOriginalConstructor()->setMethods(["create"])->getMock();
        $this->orderFactoryMock->expects($this->once())->method('create')->willReturn($this->orderMock);
    }

    /**
     * @param $quoteMock1
     */
    private function makeQuoteFactoryMock($quoteMock1)
    {
        $this->quoteFactoryMock = $this->getMockBuilder('\Magento\Quote\Model\QuoteFactory')->disableOriginalConstructor()->setMethods(["create"])->getMock();
        $this->quoteFactoryMock->expects($this->once())->method('create')->willReturn($quoteMock1);
    }
}
