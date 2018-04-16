<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManager;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD;
use Ebizmarts\SagePaySuite\Model\Config\ClosedForAction;
use Ebizmarts\SagePaySuite\Model\PI;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;
use stdClass;

class ThreeDSecureCallbackManagementTest extends \PHPUnit\Framework\TestCase
{

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
     * @expectedException \LogicException
     */
    public function testPlaceOrderError()
    {
        $objectManagerHelper = new ObjectManager($this);

        $checkoutHelperMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Helper\Checkout")
            ->disableOriginalConstructor()->getMock();

        $piTransactionResult = $objectManagerHelper->getObject(self::PI_TRANSACTION_RESULT);
        //$piTransactionResult->setData([]);

        $piTransactionResultFactoryMock = $this->getMockBuilder(self::PI_TRANSACTION_RESULT_FACTORY)
            ->setMethods(["create"])->disableOriginalConstructor()->getMock();
        $piTransactionResultFactoryMock->method("create")->willReturn($piTransactionResult);

        /** @var PiTransactionResultThreeD $threeDResult */
        $threeDResult = $objectManagerHelper->getObject(PiTransactionResultThreeD::class);
        $threeDResult->setStatus("Authenticated");

        $piRestApiMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Model\Api\PIRest")
            ->disableOriginalConstructor()->getMock();
        $piRestApiMock->expects($this->once())->method("submit3D")->willReturn($threeDResult);

        $error     = new \Magento\Framework\Phrase("Transaction not found.");
        $exception = new \Ebizmarts\SagePaySuite\Model\Api\ApiException($error);
        $piRestApiMock->expects($this->exactly(5))->method("transactionDetails")->willThrowException($exception);
        $piRestApiMock->expects($this->once())->method("void");

        $checkoutSessionMock = $this->getMockBuilder("Magento\Checkout\Model\Session")
            ->setMethods(["setData"])
            ->disableOriginalConstructor()->getMock();
        $checkoutSessionMock->expects($this->never())->method("setData");

        /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $model */
        $model = $objectManagerHelper->getObject(
            self::THREE_D_SECURE_CALLBACK_MANAGEMENT,
            [
                "checkoutHelper" => $checkoutHelperMock,
                "payResultFactory" => $piTransactionResultFactoryMock,
                "piRestApi" => $piRestApiMock,
                "checkoutSession" => $checkoutSessionMock
            ]
        );

        $requestData = $objectManagerHelper->getObject("Ebizmarts\SagePaySuite\Api\Data\PiRequestManager");
        $model->setRequestData($requestData);

        $payResult = $objectManagerHelper->getObject(self::PI_TRANSACTION_RESULT);
        $model->setPayResult($payResult);

        $model->placeOrder();
    }

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

        $piTransactionResultFactoryMock = $this->getMockBuilder(self::PI_TRANSACTION_RESULT_FACTORY)
            ->setMethods(["create"])->disableOriginalConstructor()->getMock();
        $piTransactionResultFactoryMock->method("create")->willReturn($piTransactionResult);

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
        $paymentMock->expects($this->exactly(6))->method('setAdditionalInformation');
        $paymentMock->expects($this->once())->method('save');
        $paymentMock->expects($this->once())->method('getMethodInstance')->willReturn($paymentInstanceMock);

        $invoiceCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceCollectionMock->expects($this->once())->method('setDataToAll')->willReturnSelf();

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('place')->willReturnSelf();
        $orderMock->expects($this->once())->method('save')->willReturnSelf();
        $orderMock->expects($this->once())->method('load')->with(50)->willReturnSelf();
        $orderMock->expects($this->exactly(13))->method('getPayment')->willReturn($paymentMock);
        $orderMock->expects($this->once())->method('getInvoiceCollection')->willReturn($invoiceCollectionMock);

        $orderFactoryMock = $this->getMockBuilder('\Magento\Sales\Model\OrderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $orderFactoryMock->expects($this->once())->method('create')
            ->willReturn($orderMock);

        $httpRequestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $httpRequestMock->expects($this->exactly(2))
            ->method('getParam')->willReturnOnConsecutiveCalls(50, 51);

        $closedForAction = $objectManagerHelper
            ->getObject(ClosedForAction::class, ['paymentAction' => 'Payment']);

        $actionFactoryMock = $this->getMockBuilder(self::CONFIG_CLOSED_FOR_ACTION_FACTORY)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $actionFactoryMock->expects($this->once())->method('create')->with(['paymentAction' => 'Payment'])
        ->willReturn($closedForAction);

        $transactionMock = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setOrderPaymentObject',
                    'setTxnId',
                    'setTxnType',
                    'setPaymentId',
                    'setIsClosed',
                    'save',
                    'setOrderId',
                ]
            )
            ->getMock();
        $transactionMock->expects($this->once())->method('setOrderPaymentObject');
        $transactionMock->expects($this->once())->method('setTxnId');
        $transactionMock->expects($this->once())->method('setOrderId');
        $transactionMock->expects($this->once())->method('setTxnType')->with('capture');
        $transactionMock->expects($this->once())->method('setPaymentId');
        $transactionMock->expects($this->once())->method('setIsClosed')->with(true);
        $transactionMock->expects($this->once())->method('save');

        $transactionFactoryMock = $this->getMockBuilder('\Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $transactionFactoryMock->expects($this->once())->method('create')->willReturn($transactionMock);

        /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $model */
        $model = $objectManagerHelper->getObject(
            self::THREE_D_SECURE_CALLBACK_MANAGEMENT,
            [
                'checkoutHelper'   => $checkoutHelperMock,
                'payResultFactory' => $piTransactionResultFactoryMock,
                'piRestApi'        => $piRestApiMock,
                'checkoutSession'  => $checkoutSessionMock,
                'orderFactory'     => $orderFactoryMock,
                'httpRequest'      => $httpRequestMock,
                'actionFactory'    => $actionFactoryMock,
                'transactionFactory' => $transactionFactoryMock
            ]
        );

        $requestDataMock = $this->getMockBuilder(PiRequestManager::class)
        ->disableOriginalConstructor()
        ->getMock();
        $requestDataMock->expects($this->exactly(2))->method('getPaymentAction')->willReturn('Payment');
        $model->setRequestData($requestDataMock);

        $model->placeOrder();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeThreeDMock()
    {
        $model = $this->getMockBuilder(self::THREE_D_SECURE_CALLBACK_MANAGEMENT)
            ->disableOriginalConstructor()
            ->setMethods(["getPayment"])
            ->getMock();

        return $model;
    }

}