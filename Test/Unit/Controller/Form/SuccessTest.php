<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Form;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class SuccessTest extends \PHPUnit_Framework_TestCase
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
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->method('getAdditionalInformation')
            ->willReturnOnConsecutiveCalls("100000001-2016-12-12-12346789", false);

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

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

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->method('getMode')->willReturn($mode);
        $configMock->method('getSagepayPaymentAction')->willReturn($paymentAction);

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->once())
            ->method('loadByIncrementId')
            ->willReturnSelf();

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
            ->willReturn(
                [
                    "VPSTxId"        => "{" . self::TEST_VPSTXID . "}",
                    "CardType"       => "VISA",
                    "Last4Digits"    => "0006",
                    "StatusDetail"   => "OK_STATUS_DETAIL",
                    "VendorTxCode"   => "100000001-2016-12-12-12346789",
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
        $this->quoteFactoryMock = $this->getMockBuilder('\Magento\Quote\Model\QuoteFactory')
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteMock1);

        $this->orderFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(4);

        $this->checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue($this->orderMock));

        $this->_expectRedirect("checkout/onepage/success");

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->formSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Success',
            [
                'context'            => $contextMock,
                'config'             => $configMock,
                'checkoutSession'    => $checkoutSessionMock,
                'checkoutHelper'     => $this->checkoutHelperMock,
                'formModel'          => $formModelMock,
                'quoteFactory'       => $this->quoteFactoryMock,
                'orderFactory'       => $this->orderFactoryMock,
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

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

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

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->method('getMode')->willReturn($mode);
        $configMock->method('getSagepayPaymentAction')->willReturn($paymentAction);

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->once())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->expects($this->never())->method('place');

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
            ->willReturn(
                [
                    "VPSTxId"        => "{" . self::TEST_VPSTXID . "}",
                    "CardType"       => "VISA",
                    "Last4Digits"    => "0006",
                    "StatusDetail"   => "OK_STATUS_DETAIL",
                    "VendorTxCode"   => "100000001-2016-12-12-12346789",
                    "3DSecureStatus" => "OK",
                    "Status"         => "PENDING",
                    "ExpiryDate"     => "0419",
                ]
            );

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

        $this->orderFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

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
                'config'             => $configMock,
                'checkoutSession'    => $checkoutSessionMock,
                'checkoutHelper'     => $this->checkoutHelperMock,
                'transactionFactory' => $transactionFactoryMock,
                'formModel'          => $formModelMock,
                'quoteFactory'       => $this->quoteFactoryMock,
                'orderFactory'       => $this->orderFactoryMock,
                'orderSender'        => $orderSenderMock
            ]
        );

        $this->formSuccessController->execute();
    }

    public function testExecuteERROR()
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

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

        $checkoutSessionMock = $this
            ->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quoteMock));

        $invoiceCollectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Invoice\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->once())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->method('getId')->willReturn(null);

        $this->orderFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(["create"])
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

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

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

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
                'config'             => $configMock,
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

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

        $responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

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
}
