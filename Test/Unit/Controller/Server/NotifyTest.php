<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class NotifyTest extends \PHPUnit\Framework\TestCase
{
    private $configMock;
    private $transactionFactoryMock;
    private $orderFactoryMock;
    private $contextMock;
    private $checkoutSessionMock;
    private $quoteMock;
    private $objectManagerHelper;

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var Delete
     */
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

    private $orderMock;

    // @codingStandardsIgnoreStart
    public function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
    }
    // @codingStandardsIgnoreEnd

    public function testExecuteOK()
    {
        $this->markTestSkipped();
        $serverModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Server')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));
        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($serverModelMock));

        $this->quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->checkoutSessionMock = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()->getMock();

        $this->responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getSagepayPaymentAction')
            ->will($this->returnValue("PAYMENT"));
        $this->configMock->expects($this->any())
            ->method('getVendorname')
            ->will($this->returnValue("testebizmarts"));

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
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
        $invoiceCollectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Invoice\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceCollectionMock->expects($this->once())->method('setDataToAll')->willReturnSelf();
        $this->orderMock
            ->expects($this->once())
            ->method('getInvoiceCollection')
            ->willReturn($invoiceCollectionMock);

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
                "VPSSignature" => '301680A8BBDB771C67918A6599703B10'
            ]));

        $this->_expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecutePENDING()
    {
        $orderSenderMock = $this
            ->getMockBuilder('\Magento\Sales\Model\Order\Email\Sender\OrderSender')
            ->disableOriginalConstructor()
            ->getMock();

        $serverModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Server')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));
        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($serverModelMock));
        $paymentMock->expects($this->exactly(3))->method('setAdditionalInformation');

        $this->quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->checkoutSessionMock = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()->getMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getSagepayPaymentAction')
            ->will($this->returnValue("PAYMENT"));
        $this->configMock->expects($this->any())
            ->method('getVendorname')
            ->will($this->returnValue("testebizmarts"));

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
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
                "VPSSignature"   => 'F2575F3DD8762F709DC8BB18A136DCEE'
            ]);

        $this->_expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock,
            $orderSenderMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteABORT()
    {
        $serverModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Server')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));
        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($serverModelMock));

        $this->quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->checkoutSessionMock = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()->getMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getSagepayPaymentAction')
            ->will($this->returnValue("PAYMENT"));
        $this->configMock->expects($this->any())
            ->method('getVendorname')
            ->will($this->returnValue("testebizmarts"));

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
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
                "VPSSignature" => '5D0EB35B92419D489E8BC3224A17C9E3'
            ]));

        $this->_expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction ABORTED successfully' . "\r\n" .
            'RedirectURL=?message=Transaction cancelled by customer' . "\r\n"
        );

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteStatusError()
    {
        $serverModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Server')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));
        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($serverModelMock));

        $this->quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->checkoutSessionMock = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()->getMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getSagepayPaymentAction')
            ->will($this->returnValue("PAYMENT"));
        $this->configMock->expects($this->any())
            ->method('getVendorname')
            ->will($this->returnValue("testebizmarts"));

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
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
                "VPSSignature" => 'A230EF0384C9B66C8AE7B322E3A7C9A9'
            ]));

        $this->_expectSetBody(
            'Status=INVALID' . "\r\n" .
            'StatusDetail=Payment was not accepted, please try another payment method' . "\r\n" .
            'RedirectURL=?message=Payment was not accepted, please try another payment method' . "\r\n"
        );

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteInvalidTransactionId()
    {
        $serverModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Server')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));
        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($serverModelMock));

        $this->quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->checkoutSessionMock = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()->getMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getSagepayPaymentAction')
            ->will($this->returnValue("PAYMENT"));
        $this->configMock->expects($this->any())
            ->method('getVendorname')
            ->will($this->returnValue("testebizmarts"));

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
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
                "VPSSignature" => '97EC6F77218792D1C09BEB89E7A5F0A2'
            ]));

        $this->_expectSetBody(
            'Status=INVALID' . "\r\n" .
            'StatusDetail=Something went wrong: Invalid transaction id' . "\r\n" .
            'RedirectURL=?message=Something went wrong: Invalid transaction id' . "\r\n"
        );

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteNoQuote()
    {
        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
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

        $this->checkoutSessionMock = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()->getMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

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

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock
        );

        $this->_expectSetBody(
            'Status=INVALID' . "\r\n" .
            'StatusDetail=Order was not found' . "\r\n" .
            'RedirectURL=?message=Order was not found' . "\r\n"
        );

        $this->serverNotifyController->execute();
    }

    /**
     * @param $body
     */
    private function _expectSetBody($body)
    {
        $this->responseMock->expects($this->atLeastOnce())
            ->method('setBody')
            ->with($body);
    }

    public function testExecuteWihtToken()
    {
        $this->markTestSkipped();
        $serverModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Server')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));
        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($serverModelMock));

        $this->quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->checkoutSessionMock = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()->getMock();

        $this->responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getSagepayPaymentAction')
            ->will($this->returnValue("PAYMENT"));
        $this->configMock->expects($this->any())
            ->method('getVendorname')
            ->will($this->returnValue("testebizmarts"));

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
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
        $invoiceCollectionMock->expects($this->once())->method('setDataToAll')->willReturnSelf();
        $this->orderMock
            ->expects($this->once())
            ->method('getInvoiceCollection')
            ->willReturn($invoiceCollectionMock);

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

        $this->_expectSetBody(
            'Status=OK' . "\r\n" .
            'StatusDetail=Transaction completed successfully' . "\r\n" .
            'RedirectURL=?quoteid=1' . "\r\n"
        );

        $this->serverNotifyController = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Notify',
            [
                'context'            => $this->contextMock,
                'config'             => $this->configMock,
                'checkoutSession'    => $this->checkoutSessionMock,
                'orderFactory'       => $this->orderFactoryMock,
                'transactionFactory' => $this->transactionFactoryMock,
                'quote'              => $this->quoteMock,
                'tokenModel'         => $tokenMock
            ]
        );

        $this->serverNotifyController->execute();
    }

    public function testExecuteInvalidSignature()
    {
        $serverModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Server')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));
        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($serverModelMock));

        $this->quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')->disableOriginalConstructor()->getMock();
        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->checkoutSessionMock = $this->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()->getMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\Request\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()->getMock();
        $this->configMock->expects($this->any())
            ->method('getSagepayPaymentAction')
            ->will($this->returnValue("PAYMENT"));
        $this->configMock->expects($this->any())
            ->method('getVendorname')
            ->will($this->returnValue("testebizmarts"));

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
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

        $this->_expectSetBody(
            'Status=INVALID' . "\r\n" .
            'StatusDetail=Something went wrong: Invalid VPS Signature' . "\r\n" .
            'RedirectURL=?message=Something went wrong: Invalid VPS Signature' . "\r\n"
        );

        $this->controllerInstantiate(
            $this->contextMock,
            $this->configMock,
            $this->checkoutSessionMock,
            $this->orderFactoryMock,
            $this->transactionFactoryMock,
            $this->quoteMock
        );

        $this->serverNotifyController->execute();
    }

    /**
     * @param $contextMock
     * @param $configMock
     * @param $checkoutSessionMock
     * @param $orderFactoryMock
     * @param $transactionFactoryMock
     * @param $quoteMock
     * @param null|\Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private function controllerInstantiate(
        $contextMock,
        $configMock,
        $checkoutSessionMock,
        $orderFactoryMock,
        $transactionFactoryMock,
        $quoteMock,
        $orderSender = null
    ) {
        $args = [
            'context'            => $contextMock,
            'config'             => $configMock,
            'checkoutSession'    => $checkoutSessionMock,
            'orderFactory'       => $orderFactoryMock,
            'transactionFactory' => $transactionFactoryMock,
            'quote'              => $quoteMock
        ];

        if ($orderSender !== null) {
            $args['orderSender'] = $orderSender;
        }

        $this->serverNotifyController = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Notify',
            $args
        );
    }
}
