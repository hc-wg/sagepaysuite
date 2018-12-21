<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManager;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD;
use Ebizmarts\SagePaySuite\Model\PI;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Ebizmarts\SagePaySuite\Model\Config;


class ThreeDSecureCallbackManagementTest extends \PHPUnit_Framework_TestCase
{
    /** @var InvoiceSender|\PHPUnit_Framework_MockObject_MockObject */
    private $invoiceEmailSenderMock;

    /** @var Config|\PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    const THREE_D_SECURE_CALLBACK_MANAGEMENT = "Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement";

    const PI_TRANSACTION_RESULT_FACTORY = "Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory";

    const PI_TRANSACTION_RESULT = "Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult";

    const CONFIG_CLOSED_FOR_ACTION_FACTORY = '\Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory';

    public function testIsNotMotoTransaction()
    {
        /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $model */
        $model = $this->makeThreeDMock();

        $this->assertFalse($model->getIsMotoTransaction());
    }

    /**
     * @throws \Ebizmarts\SagePaySuite\Model\Api\ApiException
     * @throws \Magento\Framework\Validator\Exception
     */
    public function testPlaceOrderOk()
    {
        $objectManagerHelper = new ObjectManager($this);

        $checkoutHelperMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Helper\Checkout")
            ->disableOriginalConstructor()->getMock();
        $checkoutHelperMock->expects($this->once())->method('sendOrderEmail');

        $piTransactionResult = $this->getMockBuilder(self::PI_TRANSACTION_RESULT)
            ->disableOriginalConstructor()
            ->getMock();
        $piTransactionResult->expects($this->once())->method('getStatus')->willReturn('Authenticated');
        $piTransactionResult->expects($this->exactly(2))->method('getStatusCode')->willReturn('0000');

        $piTransactionResultFactoryMock = $this
            ->getMockBuilder(self::PI_TRANSACTION_RESULT_FACTORY)
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();
        $piTransactionResultFactoryMock
            ->method("create")
            ->willReturn($piTransactionResult);

        /** @var PiTransactionResultThreeD $threeDResult */
        $threeDResult = $objectManagerHelper->getObject(PiTransactionResultThreeD::class);
        $threeDResult->setStatus('Authenticated');

        $piRestApiMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Model\Api\PIRest")
            ->disableOriginalConstructor()->getMock();
        $piRestApiMock->expects($this->once())->method("submit3D")->willReturn($threeDResult);

        $transactionDetailsMock = $this->getMockBuilder(PiTransactionResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transactionDetailsMock->expects($this->once())->method('getPaymentMethod');
        $transactionDetailsMock->expects($this->once())->method('getStatusDetail');
        $transactionDetailsMock->expects($this->exactly(2))->method('getStatusCode')->willReturn('0000');
        $transactionDetailsMock->expects($this->once())->method('getThreeDSecure');
        $transactionDetailsMock->expects($this->once())->method('getTransactionId');

        $piRestApiMock->expects($this->once())->method('transactionDetails')
            ->willReturn($transactionDetailsMock);
        $piRestApiMock->expects($this->never())->method('void');

        $checkoutSessionMock = $this->getMockBuilder("Magento\Checkout\Model\Session")
            ->setMethods([
                'setData',
                'clearHelperData',
                'setLastQuoteId',
                'setLastSuccessQuoteId',
                'setLastOrderId',
                'setLastRealOrderId',
                'setLastOrderStatus',
            ])
            ->disableOriginalConstructor()->getMock();
        $checkoutSessionMock->expects($this->once())
            ->method('setData')->with('sagepaysuite_presaved_order_pending_payment', null);
        $checkoutSessionMock->expects($this->once())->method('clearHelperData');
        $checkoutSessionMock->expects($this->once())->method('setLastQuoteId');
        $checkoutSessionMock->expects($this->once())->method('setLastSuccessQuoteId');
        $checkoutSessionMock->expects($this->once())->method('setLastOrderId');
        $checkoutSessionMock->expects($this->once())->method('setLastRealOrderId');
        $checkoutSessionMock->expects($this->once())->method('setLastOrderStatus');

        $paymentInstanceMock = $this->getMockBuilder(PI::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(['setTransactionId', 'setMethod', 'setAdditionalInformation', 'save', 'getMethodInstance'])
            ->getMock();
        $paymentMock->expects($this->once())->method('setMethod');
        $paymentMock->expects($this->once())->method('setTransactionId');
        $paymentMock->expects($this->exactly(8))->method('setAdditionalInformation');
        $paymentMock->expects($this->once())->method('save');
        $paymentMock->expects($this->once())->method('getMethodInstance')->willReturn($paymentInstanceMock);

        $invoiceCollectionMock = $this
            ->getMockBuilder(Collection::class)
            ->setMethods(['setDataToAll', 'count', 'getFirstItem', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceCollectionMock->expects($this
            ->once())
            ->method('setDataToAll')
            ->willReturnSelf();
        $invoiceCollectionMock
            ->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $orderMock = $this
            ->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock
            ->expects($this->once())
            ->method('place')
            ->willReturnSelf();
        $orderMock
            ->expects($this->exactly(15))
            ->method('getPayment')
            ->willReturn($paymentMock);
        $orderMock
            ->expects($this->exactly(2))
            ->method('getInvoiceCollection')
            ->willReturn($invoiceCollectionMock);
        $orderMock
            ->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $orderFactoryMock = $this
            ->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($orderMock);


        $httpRequestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $httpRequestMock->expects($this->exactly(2))
            ->method('getParam')->willReturnOnConsecutiveCalls(50, 51);

        $transactionFactoryMock = $this->getMockBuilder('\Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceEmailSenderMock = $this
            ->getMockBuilder(InvoiceSender::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this
            ->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock
            ->expects($this->once())
            ->method('getInvoiceConfirmationNotification')
            ->willReturn("1");
        $this->configMock
            ->expects($this->once())
            ->method('getSagepayPaymentAction')
            ->willReturn(Config::ACTION_PAYMENT_PI);

        $invoiceMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Invoice::class)
            ->disableOriginalConstructor()
            ->getMock();

        $invoiceCollectionMock
            ->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($invoiceMock);

        /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $model */
        $model = $objectManagerHelper->getObject(
            self::THREE_D_SECURE_CALLBACK_MANAGEMENT,
            [
                'checkoutHelper'     => $checkoutHelperMock,
                'payResultFactory'   => $piTransactionResultFactoryMock,
                'piRestApi'          => $piRestApiMock,
                'checkoutSession'    => $checkoutSessionMock,
                'orderFactory'       => $orderFactoryMock,
                'httpRequest'        => $httpRequestMock,
                'transactionFactory' => $transactionFactoryMock,
                'invoiceEmailSender' => $this->invoiceEmailSenderMock,
                'config'             => $this->configMock
            ]
        );

        $this->invoiceEmailSenderMock
            ->expects($this->once())
            ->method('send')
            ->with($invoiceMock)
            ->willReturn(true);

        $requestDataMock = $this->getMockBuilder(PiRequestManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestDataMock->expects($this->once())->method('getPaymentAction')->willReturn(Config::ACTION_PAYMENT_PI);
        $model->setRequestData($requestDataMock);

        $model->placeOrder();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeThreeDMock()
    {
        $model = $this->getMockBuilder("Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement")
            ->disableOriginalConstructor()
            ->setMethods(["getPayment"])
            ->getMock();

        return $model;
    }

}
