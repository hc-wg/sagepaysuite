<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class NotifyTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var Delete
     */
    protected $serverNotifyController;

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
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    protected function setUp()
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

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();


        $checkoutSessionMock = $this
            ->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();

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
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->any())
            ->method('getSagepayPaymentAction')
            ->will($this->returnValue("PAYMENT"));
        $configMock->expects($this->any())
            ->method('getVendorname')
            ->will($this->returnValue("testebizmarts"));

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();
        $orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $orderMock->expects($this->any())
            ->method('getInvoiceCollection')
            ->will($this->returnValue([]));
        $orderMock->expects($this->any())
            ->method('cancel')
            ->willReturnSelf();

        $orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderMock));

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


        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->serverNotifyController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Notify',
            [
                'context' => $contextMock,
                'config' => $configMock,
                'checkoutSession' => $checkoutSessionMock,
                'orderFactory' => $orderFactoryMock,
                'transactionFactory' => $transactionFactoryMock,
                'quote' => $quoteMock
            ]
        );
    }

    public function testExecuteOK()
    {
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

        $this->serverNotifyController->execute();
    }

    public function testExecuteABORT()
    {
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

        $this->serverNotifyController->execute();
    }

    public function testExecuteERROR()
    {
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

        $this->serverNotifyController->execute();
    }

    /**
     * @param $body
     */
    protected function _expectSetBody($body)
    {
        $this->responseMock->expects($this->atLeastOnce())
            ->method('setBody')
            ->with($body);
    }
}
