<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\OrderFactory;
use stdClass;

class NotifyTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Config|\PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var TransactionFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $transactionFactoryMock;

    /** @var OrderFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $orderFactoryMock;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    private $checkoutSessionMock;

    /** @var Quote|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteMock;

    /** @var ObjectManagerHelper */
    private $objectManagerHelper;

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /** @var \Ebizmarts\SagePaySuite\Controller\Server\Notify */
    private $serverNotifyController;

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

    /** @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject */
    private $orderMock;

    // @codingStandardsIgnoreStart
    public function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
    }
    // @codingStandardsIgnoreEnd

    public function testExecuteOK()
    {
        $serverModelMock = $this->makeServerModelMock();

        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);

        $this->makeQuoteMock();


        $this->makeHttpResponseMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $this->makeConfigMockPayment();

        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->never())
            ->method('cancel')
            ->willReturnSelf();

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));
        $this->orderMock
            ->expects($this->never())
            ->method('getInvoiceCollection');

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "TxType" => "PAYMENT",
                "Status" => "OK",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "StatusDetail" => "OK Status",
                "3DSecureStatus" => "NOTCHECKED",
                "BankAuthCode" => "999777",
                "TxAuthNo" => "17962849",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "ExpiryDate" => "0222",
                "VendorTxCode" => "10000000001-2015-12-12-123456",
                "AVSCV2" => "OK",
                "AddressResult" => "OK",
                "PostCodeResult" => "OK",
                "CV2Result" => "OK",
                "GiftAid" => "0",
                "AddressStatus" => "OK",
                "PayerStatus" => "OK",
                "VPSSignature" => '8E77F29220981737C51C615C3464301F'
            ]));

        $this->expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );

        $helperMock = $this->makeHelperMockCalledOnce();

        $updateOrderMock = $this->getMockBuilder(OrderUpdateOnCallback::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateOrderMock->expects($this->once())->method('setOrder')->with($this->orderMock);
        $updateOrderMock->expects($this->once())->method('confirmPayment')->with(self::TEST_VPSTXID);

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock,
            null,
            $updateOrderMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteOkSagePayRetry()
    {
        $serverModelMock = $this->makeServerModelMock();

        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);

        $this->makeQuoteMock();

        $this->makeHttpResponseMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $this->makeConfigMockPayment();

        $this->makeOrderMock($paymentMock);

        $this->orderMock->expects($this->never())
            ->method('cancel')
            ->willReturnSelf();

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                'TxType'         => 'PAYMENT',
                'Status'         => 'OK',
                'VPSTxId'        => '{' . self::TEST_VPSTXID . '}',
                'StatusDetail'   => 'OK Status',
                '3DSecureStatus' => 'NOTCHECKED',
                "BankAuthCode" => "999777",
                "TxAuthNo" => "17962849",
                'CardType'       => 'VISA',
                'Last4Digits'    => '0006',
                'ExpiryDate'     => '0222',
                'VendorTxCode'   => '10000000001-2015-12-12-123456',
                'AVSCV2'         => 'OK',
                'AddressResult'  => 'OK',
                'PostCodeResult' => 'OK',
                'CV2Result'      => 'OK',
                'GiftAid'        => '0',
                'AddressStatus'  => 'OK',
                'PayerStatus'    => 'OK',
                'VPSSignature' => '8E77F29220981737C51C615C3464301F'
            ]));

        $this->expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );

        $helperMock = $this->makeHelperMockCalledOnce();

        $updateOrderMock = $this->getMockBuilder(OrderUpdateOnCallback::class)
        ->disableOriginalConstructor()
        ->getMock();
        $updateOrderMock->expects($this->once())->method('setOrder')->with($this->orderMock);
        $updateOrderMock->expects($this->once())->method('confirmPayment')->with(self::TEST_VPSTXID)
        ->willThrowException(new AlreadyExistsException(__('Transaction already exists.')));

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock,
            null,
            $updateOrderMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecutePENDING()
    {
        $orderSenderMock = $this
            ->getMockBuilder('\Magento\Sales\Model\Order\Email\Sender\OrderSender')
            ->disableOriginalConstructor()
            ->getMock();

        $serverModelMock = $this->makeServerModelMock();

        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);
        $paymentMock->expects($this->exactly(5))->method('setAdditionalInformation');

        $this->makeQuoteMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $this->makeConfigMockPayment();

        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->never())
            ->method('cancel');

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));
        $invoiceCollectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Invoice\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceCollectionMock->expects($this->never())->method('setDataToAll')->willReturnSelf();
        $this->orderMock
            ->expects($this->never())
            ->method('getInvoiceCollection');

        $orderSenderMock->expects($this->once())->method('send')->with($this->orderMock);

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn((object)[
                "TxType"         => "PAYMENT",
                "Status"         => "PENDING",
                "VPSTxId"        => "{" . self::TEST_VPSTXID . "}",
                "StatusDetail"   => "OK Status",
                "3DSecureStatus" => "NOTCHECKED",
                "BankAuthCode" => "999777",
                "TxAuthNo" => "17962849",
                "CardType"       => "VISA",
                "Last4Digits"    => "0006",
                "ExpiryDate"     => "0222",
                "VendorTxCode"   => "10000000001-2015-12-12-123456",
                "AVSCV2"         => "OK",
                "AddressResult"  => "OK",
                "PostCodeResult" => "OK",
                "CV2Result"      => "OK",
                "GiftAid"        => "0",
                "AddressStatus"  => "OK",
                "PayerStatus"    => "OK",
                "VPSSignature"   => '5E3C9B48732834181EBA17ACDE1E55EF'
            ]);

        $this->expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );

        $helperMock = $this->makeHelperMockCalledOnce();

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock,
            $orderSenderMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteABORT()
    {
        $serverModelMock = $this->makeServerModelMock();

        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);

        $this->makeQuoteMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $this->makeConfigMockPayment();

        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->once())
            ->method('cancel')
            ->willReturnSelf();

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "TxType" => "PAYMENT",
                "Status" => "ABORT",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "StatusDetail" => "ABORT Status",
                "3DSecureStatus" => "NOTCHECKED",
                "BankAuthCode" => "999777",
                "TxAuthNo" => "17962849",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "ExpiryDate" => "0222",
                "VendorTxCode" => "10000000001-2015-12-12-123456",
                "AVSCV2" => "OK",
                "AddressResult" => "OK",
                "PostCodeResult" => "OK",
                "CV2Result" => "OK",
                "GiftAid" => "0",
                "AddressStatus" => "OK",
                "PayerStatus" => "OK",
                "VPSSignature" => 'EA6C59BD4DBEDB8B8B59345E64F9A02C'
            ]));

        $this->expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction ABORTED successfully' . "\r\n" .
            'RedirectURL=?quote=1&message=Transaction cancelled by customer' . "\r\n"
        );

        $helperMock = $this->makeHelperMockCalledOnce();

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteStatusError()
    {
        $serverModelMock = $this->makeServerModelMock();

        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);

        $this->makeQuoteMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $this->makeConfigMockPayment();

        $this->makeOrderMock($paymentMock);
        $this->orderMock
            ->expects($this->once())
            ->method('cancel')
            ->willReturnSelf();
        $this->orderMock
            ->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "TxType" => "PAYMENT",
                "Status" => "ERROR",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "StatusDetail" => "ABORT Status",
                "3DSecureStatus" => "NOTCHECKED",
                "BankAuthCode" => "999777",
                "TxAuthNo" => "17962849",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "ExpiryDate" => "0222",
                "VendorTxCode" => "10000000001-2015-12-12-123456",
                "AVSCV2" => "OK",
                "AddressResult" => "OK",
                "PostCodeResult" => "OK",
                "CV2Result" => "OK",
                "GiftAid" => "0",
                "AddressStatus" => "OK",
                "PayerStatus" => "OK",
                "VPSSignature" => 'F348327D868D37850E361B75A2B1D885'
            ]));

        $this->expectSetBody(
            'Status=INVALID' . "\r\n" .
            'StatusDetail=Payment was not accepted, please try another payment method' . "\r\n" .
            'RedirectURL=?message=Payment was not accepted, please try another payment method' . "\r\n"
        );

        $helperMock = $this->makeHelperMockCalledOnce();

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteInvalidTransactionId()
    {
        $serverModelMock = $this->makeServerModelMock();

        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);

        $this->makeQuoteMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $this->makeConfigMockPayment();

        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->any())
            ->method('cancel')
            ->willReturnSelf();

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "TxType" => "PAYMENT",
                "Status" => "OK",
                "VPSTxId" => "{" . "INVALID_TRANSACTION" . "}",
                "StatusDetail" => "ABORT Status",
                "3DSecureStatus" => "NOTCHECKED",
                "CardType" => "VISA",
                "BankAuthCode" => "999777",
                "TxAuthNo" => "17962849",
                "Last4Digits" => "0006",
                "ExpiryDate" => "0222",
                "ExpiryDate" => "0222",
                "VendorTxCode" => "10000000001-2015-12-12-123456",
                "AVSCV2" => "OK",
                "AddressResult" => "OK",
                "PostCodeResult" => "OK",
                "CV2Result" => "OK",
                "GiftAid" => "0",
                "AddressStatus" => "OK",
                "PayerStatus" => "OK",
                "VPSSignature" => '01C00A6026B02C534200728C4E85DDA3'
            ]));

        $this->expectSetBody(
            'Status=INVALID' . "\r\n" .
            'StatusDetail=Something went wrong: Invalid transaction id' . "\r\n" .
            'RedirectURL=?message=Something went wrong: Invalid transaction id' . "\r\n"
        );

        $helperMock = $this->makeHelperMockCalledOnce('INVALID_TRANSACTION');

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteNoBankAuthCode()
    {
        $serverModelMock = $this->makeServerModelMock();
        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);
        $paymentMock->expects($this->exactly(3))->method('setAdditionalInformation');
        $this->makeQuoteMock();
        $this->makeHttpResponseMock();
        $this->makeHttpRequestMock();
        $this->makeUrlBuilderMock();
        $this->makeContextMock();
        $this->makeConfigMockPayment();
        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->never())
            ->method('cancel')
            ->willReturnSelf();
        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);
        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));
        $this->orderMock
            ->expects($this->never())
            ->method('getInvoiceCollection');
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "TxType" => "PAYMENT",
                "Status" => "OK",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "StatusDetail" => "OK Status",
                "3DSecureStatus" => "NOTCHECKED",
                "TxAuthNo" => "17962849",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "ExpiryDate" => "0222",
                "VendorTxCode" => "10000000001-2015-12-12-123456",
                "AVSCV2" => "OK",
                "AddressResult" => "OK",
                "PostCodeResult" => "OK",
                "CV2Result" => "OK",
                "GiftAid" => "0",
                "AddressStatus" => "OK",
                "PayerStatus" => "OK",
                "VPSSignature" => "4B27106C4F30903A434176C5807AE4A3"
            ]));
        $this->expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );
        $helperMock = $this->makeHelperMockCalledOnce();
        $updateOrderMock = $this->getMockBuilder(OrderUpdateOnCallback::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateOrderMock->expects($this->once())->method('setOrder')->with($this->orderMock);
        $updateOrderMock->expects($this->once())->method('confirmPayment')->with(self::TEST_VPSTXID);
        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock,
            null,
            $updateOrderMock
        );
        $this->serverNotifyController->execute();
    }

    public function testExecuteNoTxAuthCode()
    {
        $serverModelMock = $this->makeServerModelMock();
        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);
        $paymentMock->expects($this->exactly(3))->method('setAdditionalInformation');
        $this->makeQuoteMock();
        $this->makeHttpResponseMock();
        $this->makeHttpRequestMock();
        $this->makeUrlBuilderMock();
        $this->makeContextMock();
        $this->makeConfigMockPayment();
        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->never())
            ->method('cancel')
            ->willReturnSelf();
        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);
        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));
        $this->orderMock
            ->expects($this->never())
            ->method('getInvoiceCollection');
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "TxType" => "PAYMENT",
                "Status" => "OK",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "StatusDetail" => "OK Status",
                "3DSecureStatus" => "NOTCHECKED",
                "BankAuthCode" => "999777",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "ExpiryDate" => "0222",
                "VendorTxCode" => "10000000001-2015-12-12-123456",
                "AVSCV2" => "OK",
                "AddressResult" => "OK",
                "PostCodeResult" => "OK",
                "CV2Result" => "OK",
                "GiftAid" => "0",
                "AddressStatus" => "OK",
                "PayerStatus" => "OK",
                "VPSSignature" => "FF89BBB5FE43019620C21F3E763179BB"
            ]));
        $this->expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );
        $helperMock = $this->makeHelperMockCalledOnce();
        $updateOrderMock = $this->getMockBuilder(OrderUpdateOnCallback::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateOrderMock->expects($this->once())->method('setOrder')->with($this->orderMock);
        $updateOrderMock->expects($this->once())->method('confirmPayment')->with(self::TEST_VPSTXID);
        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock,
            null,
            $updateOrderMock
        );
        $this->serverNotifyController->execute();
    }
    public function testExecuteNoTxAuthCodeOrBankAutCode()
    {
        $serverModelMock = $this->makeServerModelMock();
        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);
        $paymentMock->expects($this->exactly(2))->method('setAdditionalInformation');
        $this->makeQuoteMock();
        $this->makeHttpResponseMock();
        $this->makeHttpRequestMock();
        $this->makeUrlBuilderMock();
        $this->makeContextMock();
        $this->makeConfigMockPayment();
        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->never())
            ->method('cancel')
            ->willReturnSelf();
        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);
        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));
        $this->orderMock
            ->expects($this->never())
            ->method('getInvoiceCollection');
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "TxType" => "PAYMENT",
                "Status" => "OK",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "StatusDetail" => "OK Status",
                "3DSecureStatus" => "NOTCHECKED",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "ExpiryDate" => "0222",
                "VendorTxCode" => "10000000001-2015-12-12-123456",
                "AVSCV2" => "OK",
                "AddressResult" => "OK",
                "PostCodeResult" => "OK",
                "CV2Result" => "OK",
                "GiftAid" => "0",
                "AddressStatus" => "OK",
                "PayerStatus" => "OK",
                "VPSSignature" => "301680A8BBDB771C67918A6599703B10"
            ]));
        $this->expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );
        $helperMock = $this->makeHelperMockCalledOnce();
        $updateOrderMock = $this->getMockBuilder(OrderUpdateOnCallback::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateOrderMock->expects($this->once())->method('setOrder')->with($this->orderMock);
        $updateOrderMock->expects($this->once())->method('confirmPayment')->with(self::TEST_VPSTXID);
        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock,
            null,
            $updateOrderMock
        );
        $this->serverNotifyController->execute();
    }

    public function testExecuteNoQuote()
    {
        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getId', 'load'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())->method('load')->willReturnSelf();
        $quoteMock->expects($this->once())->method('getId')->willReturn(null);

        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with('Status=INVALID' . "\r\n" .
                'StatusDetail=Unable to find quote' . "\r\n" .
                'RedirectURL=?message=Unable to find quote' . "\r\n");

        $serverNotifyController = $this
            ->objectManagerHelper
            ->getObject(
                'Ebizmarts\SagePaySuite\Controller\Server\Notify',
                [
                    'context' => $this->contextMock,
                    'quote'   => $quoteMock
                ]
            );

        $serverNotifyController->execute();
    }

    public function testOrderDoesNotExist()
    {
        $this->quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $this->quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $this->quoteMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()->getMock();

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $this->transactionFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock = $this->makeHelperMockCalledNever();

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock
        );

        $this->expectSetBody(
            'Status=INVALID' . "\r\n" .
            'StatusDetail=Order was not found' . "\r\n" .
            'RedirectURL=?message=Order was not found' . "\r\n"
        );

        $this->serverNotifyController->execute();
    }

    /**
     * @param $body
     */
    private function expectSetBody($body)
    {
        $this->responseMock->expects($this->atLeastOnce())
            ->method('setBody')
            ->with($body);
    }

    public function testExecuteWihtToken()
    {
        $serverModelMock = $this->makeServerModelMock();

        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);

        $this->makeQuoteMock();

        $this->makeHttpResponseMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $this->makeConfigMockPayment();

        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(4);
        $this->orderMock->expects($this->any())
            ->method('cancel')
            ->willReturnSelf();

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));
        $invoiceCollectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Invoice\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceCollectionMock->expects($this->never())->method('setDataToAll')->willReturnSelf();
        $this->orderMock
            ->expects($this->never())
            ->method('getInvoiceCollection');

        $tokenMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Token::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenMock->expects($this->once())->method('saveToken')->with(
            4,
            'DB771C67918A659',
            'VISA',
            '0006',
            '02',
            '22',
            'testebizmarts'
        )
        ->willReturnSelf();

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "TxType" => "PAYMENT",
                "Status" => "OK",
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "StatusDetail" => "OK Status",
                "3DSecureStatus" => "NOTCHECKED",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "ExpiryDate" => "0222",
                "VendorTxCode" => "10000000001-2015-12-12-123456",
                "AVSCV2" => "OK",
                "AddressResult" => "OK",
                "PostCodeResult" => "OK",
                "CV2Result" => "OK",
                "GiftAid" => "0",
                "AddressStatus" => "OK",
                "PayerStatus" => "OK",
                'Token' => 'DB771C67918A659',
                "VPSSignature" => '301680A8BBDB771C67918A6599703B10'
            ]));

        $this->expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );

        $helperMock = $this->makeHelperMockCalledOnce();

        $this->serverNotifyController = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Notify',
            [
                'context'            => $this->contextMock,
                'config'             => $this->configMock,
                'orderFactory'       => $this->orderFactoryMock,
                'transactionFactory' => $this->transactionFactoryMock,
                'quote'              => $this->quoteMock,
                'tokenModel'         => $tokenMock,
                'suiteHelper'        => $helperMock
            ]
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteInvalidSignature()
    {
        $serverModelMock = $this->makeServerModelMock();

        $paymentMock = $this->makeOrderPaymentMock($serverModelMock);

        $this->makeQuoteMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->makeHttpRequestMock();

        $this->makeUrlBuilderMock();

        $this->makeContextMock();

        $this->makeConfigMockPayment();

        $this->makeOrderMock($paymentMock);
        $this->orderMock->expects($this->any())
            ->method('cancel')
            ->willReturnSelf();

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "TxType" => "PAYMENT",
                "Status" => "OK",
                "VPSTxId" => "{" . "INVALID_TRANSACTION" . "}",
                "StatusDetail" => "ABORT Status",
                "3DSecureStatus" => "NOTCHECKED",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "ExpiryDate" => "0222",
                "ExpiryDate" => "0222",
                "VendorTxCode" => "10000000001-2015-12-12-123456",
                "AVSCV2" => "OK",
                "AddressResult" => "OK",
                "PostCodeResult" => "OK",
                "CV2Result" => "OK",
                "GiftAid" => "0",
                "AddressStatus" => "OK",
                "PayerStatus" => "OK",
                "VPSSignature" => '123123123ads123'
            ]));

        $this->expectSetBody(
            'Status=INVALID' . "\r\n" .
            'StatusDetail=Something went wrong: Invalid VPS Signature' . "\r\n" .
            'RedirectURL=?message=Something went wrong: Invalid VPS Signature' . "\r\n"
        );

        $helperMock = $this->makeHelperMockCalledOnce();

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $helperMock
        );

        $this->serverNotifyController->execute();
    }

    /**
     * @param Context $contextMock
     * @param Config $configMock
     * @param OrderFactory $orderFactoryMock
     * @param TransactionFactory $transactionFactoryMock
     * @param Quote $quoteMock
     * @param \Ebizmarts\SagePaySuite\Helper\Data $helperMock
     * @param OrderSender $orderSender
     */
    private function controllerInstantiate(
        Context $contextMock,
        Config $configMock,
        OrderFactory $orderFactoryMock,
        TransactionFactory $transactionFactoryMock,
        Quote $quoteMock,
        Data $helperMock,
        OrderSender $orderSender = null,
        OrderUpdateOnCallback $updateOrderCallback = null
    ) {
        $args = [
            'context'            => $contextMock,
            'config'             => $configMock,
            'orderFactory'       => $orderFactoryMock,
            'transactionFactory' => $transactionFactoryMock,
            'quote'              => $quoteMock,
            'suiteHelper'        => $helperMock
        ];

        if ($orderSender !== null) {
            $args['orderSender'] = $orderSender;
        }

        if ($updateOrderCallback !== null) {
            $args['updateOrderCallback'] = $updateOrderCallback;
        }

        $this->serverNotifyController = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Notify',
            $args
        );
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private function makeHelperMockCalledNever()
    {
        $helperMock = $this->makeSuiteHelperMock();
        $helperMock->expects($this->never())->method("removeCurlyBraces");

        return $helperMock;
    }

    /**
     * @param string $returnValue
     * @return Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private function makeHelperMockCalledOnce($returnValue = null)
    {
        if ($returnValue === null) {
            $returnValue = self::TEST_VPSTXID;
        }

        $helperMock = $this->makeSuiteHelperMock();
        $helperMock->expects($this->once())->method("removeCurlyBraces")->willReturn($returnValue);

        return $helperMock;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private function makeSuiteHelperMock()
    {
        $helperMock = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();

        return $helperMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeServerModelMock()
    {
        $serverModelMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Server')->disableOriginalConstructor()->getMock();

        return $serverModelMock;
    }

    private function makeQuoteMock()
    {
        $this->quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $this->quoteMock->expects($this->any())->method('getId')->will($this->returnValue(1));
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
    }

    private function makeConfigMockPayment()
    {
        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())->method('getSagepayPaymentAction')->will($this->returnValue("PAYMENT"));
        $this->configMock->expects($this->any())->method('getVendorname')->will($this->returnValue("testebizmarts"));
    }

    /**
     * @param $serverModelMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeOrderPaymentMock($serverModelMock)
    {
        $paymentMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')->disableOriginalConstructor()->getMock();
        $paymentMock->expects($this->any())->method('getLastTransId')->will($this->returnValue(self::TEST_VPSTXID));
        $paymentMock->expects($this->any())->method('getMethodInstance')->will($this->returnValue($serverModelMock));

        return $paymentMock;
    }

    private function makeHttpResponseMock()
    {
        $this->responseMock = $this->getMockBuilder('Magento\Framework\App\Response\Http')
        ->disableOriginalConstructor()->getMock();
    }

    private function makeHttpRequestMock()
    {
        $this->requestMock = $this->getMockBuilder('Magento\Framework\App\Request\Http')->disableOriginalConstructor()->getMock();
    }

    private function makeUrlBuilderMock()
    {
        $this->urlBuilderMock = $this->getMockBuilder('Magento\Framework\UrlInterface')->disableOriginalConstructor()->getMock();
    }

    private function makeContextMock()
    {
        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())->method('getRequest')->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())->method('getResponse')->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())->method('getUrl')->will($this->returnValue($this->urlBuilderMock));
    }

    /**
     * @param $paymentMock
     */
    private function makeOrderMock($paymentMock)
    {
        $this->orderMock = $this->getMockBuilder('Magento\Sales\Model\Order')->disableOriginalConstructor()->getMock();
        $this->orderMock->expects($this->any())->method('getPayment')->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())->method('loadByIncrementId')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('place')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('getId')->will($this->returnValue(1));
    }
}
