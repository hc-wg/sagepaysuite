<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManager;
use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerInterface;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD;
use Ebizmarts\SagePaySuite\Model\Api\PIRest;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Config\ClosedForAction;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;
use Ebizmarts\SagePaySuite\Model\PI;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class ThreeDSecureCallbackManagementTest extends \PHPUnit_Framework_TestCase
{
    const THREE_D_SECURE_CALLBACK_MANAGEMENT = "Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement";

    const PI_TRANSACTION_RESULT_FACTORY = "Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory";

    const PI_TRANSACTION_RESULT = "Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult";

    const CONFIG_CLOSED_FOR_ACTION_FACTORY = '\Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory';

    const QUOTE_ID1 = '50';

    const ENCRYPTED_QUOTE_ID1 = '0:3:slozTfXK0r1J23OPKHZkGsqJqT4wudHXPZJXxE9S';

    const ENCODED_QUOTE_ID1 = 'MDozOiswMXF3V0l1WFRLTDRra0wxUCtYSGgyQVdORUdWaXNPN3N5RUNEbzE,';

    const QUOTE_ID2 = '51';

    const ENCRYPTED_QUOTE_ID2 = '0:3:hm2arLCQeFcC1C0kU6CEoy06RnjtBZ1jzMomH3+A';

    const ENCODED_QUOTE_ID2 = 'MDozOlBxWWxwSHdsUklEa3dLY0Q2TlVJTE9YOEZjYjNCbWY2VUVaT1QrN2U,';

    /** @var InvoiceSender|\PHPUnit_Framework_MockObject_MockObject */
    private $invoiceEmailSenderMock;

    /** @var Config|\PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var PiTransactionResultFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $payResultFactoryMock;

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
        $piTransactionResult->expects($this->exactly(2))->method('getStatus')->willReturn('Authenticated');
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


        $httpRequestMock = $this
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $httpRequestMock
            ->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnOnConsecutiveCalls(self::ENCODED_QUOTE_ID1, self::ENCODED_QUOTE_ID2);

        $cryptAndCodeMock = $this
            ->getMockBuilder(CryptAndCodeData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cryptAndCodeMock
            ->expects($this->exactly(2))
            ->method('decodeAndDecrypt')
            ->withConsecutive([self::ENCODED_QUOTE_ID1], [self::ENCODED_QUOTE_ID2])
            ->willReturnOnConsecutiveCalls(self::QUOTE_ID1, self::QUOTE_ID2);

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
                'config'             => $this->configMock,
                'cryptAndCode'       => $cryptAndCodeMock
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

    public function placeOrderOkProvider()
    {
        return [
            'Payment payment action' => ['Payment', 0, 0, 1, 1, 1, 2],
            'Deferred payment action' => ['Deferred', 1, 1, 0, 0, 0, 0]
        ];
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

    /**
    * @dataProvider payDataProvider
    */
    public function testPayOk($data)
    {
        $cres = $data['expectedCres'];
        $pares = 'vfewvfeaefvasdfargaasdfweq';
        $trnId = '12345asdf';
        $status = 'Ok';

        $threeDSecureCallbackManagementMock = $this->makeThreeDCallbackManagementMock();
        $piTransactionResultMock = $this
            ->getMockBuilder(PiTransactionResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRequestManagerMock = $this
            ->getMockBuilder(PiRequestManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRestApiMock = $this
            ->getMockBuilder(PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piTransactionResultThreeDMock = $this
            ->getMockBuilder(PiTransactionResultThreeD::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->payResultFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($piTransactionResultMock);

        $threeDSecureCallbackManagementMock
            ->expects($this->once())
            ->method('setPayResult')
            ->with($piTransactionResultMock);
        $threeDSecureCallbackManagementMock
            ->expects($this->exactly($data['expectsGetRequestData']))
            ->method('getRequestData')
            ->willReturn($piRequestManagerMock);

        $piRequestManagerMock
            ->expects($this->once())
            ->method('getCres')
            ->willReturn($cres);
        $piRequestManagerMock
            ->expects($this->exactly($data['expectsGetPares']))
            ->method('getParEs')
            ->willReturn($pares);
        $piRequestManagerMock
            ->expects($this->once())
            ->method('getTransactionId')
            ->willReturn($trnId);

        $threeDSecureCallbackManagementMock
            ->expects($this->once())
            ->method('getPiRestApi')
            ->willReturn($piRestApiMock);

        $piRestApiMock
            ->expects($this->exactly($data['expectsSubmit3D']))
            ->method('submit3D')
            ->willReturn($piTransactionResultThreeDMock);
        $piRestApiMock
            ->expects($this->exactly($data['expectsSubmit3Dv2']))
            ->method('submit3Dv2')
            ->willReturn($piTransactionResultThreeDMock);

        $threeDSecureCallbackManagementMock
            ->expects($this->exactly(2))
            ->method('getPayResult')
            ->willReturn($piTransactionResultMock);

        $piTransactionResultThreeDMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $piTransactionResultMock
            ->expects($this->once())
            ->method('setStatus')
            ->with($status)
            ->willReturn($piTransactionResultMock);

        $threeDSecureCallbackManagementMock->pay();
    }

    public function payDataProvider()
    {
        return [
            'test 3Dv1' => [
                [
                    'expectedCres'          => null,
                    'expectsGetPares'       => 1,
                    'expectsGetRequestData' => 3,
                    'expectsSubmit3D'       => 1,
                    'expectsSubmit3Dv2'     => 0
                ]
            ],
            'test 3Dv2' => [
                [
                    'expectedCres'          => 'fasdfrgsfdgargsdgsgrs',
                    'expectsGetPares'       => 0,
                    'expectsGetRequestData' => 2,
                    'expectsSubmit3D'       => 0,
                    'expectsSubmit3Dv2'     => 1
                ]
            ]
        ];
    }

    private function makeThreeDCallbackManagementMock()
    {
        $checkoutMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Checkout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sagePayCardTypeMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config\SagePayCardType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRequestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PiRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dataMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piResultMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\PiResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock = $this
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transactionFactoryMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment\TransactionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->payResultFactoryMock = $this
            ->getMockBuilder(PiTransactionResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderFactoryMock = $this
            ->getMockBuilder(\Magento\Sales\Model\OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceSenderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Email\Sender\InvoiceSender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cryptAndCodeDataMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\CryptAndCodeData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $threeDSecureCallbackManagementMock = $this
            ->getMockBuilder(ThreeDSecureCallbackManagement::class)
            ->setMethods([
                'setPayResult',
                'getRequestData',
                'getPiRestApi',
                'getPayResult'
            ])
            ->setConstructorArgs([
                'checkoutHelper'     => $checkoutMock,
                'piRestApi'          => $piRestMock,
                'ccConvert'          => $sagePayCardTypeMock,
                'piRequest'          => $piRequestMock,
                'suiteHelper'        => $dataMock,
                'result'             => $piResultMock,
                'checkoutSession'    => $sessionMock,
                'httpRequest'        => $requestMock,
                'orderFactory'       => $orderFactoryMock,
                'transactionFactory' => $transactionFactoryMock,
                'payResultFactory'   => $this->payResultFactoryMock,
                'invoiceEmailSender' => $invoiceSenderMock,
                'config'             => $configMock,
                'cryptAndCode'       => $cryptAndCodeDataMock
            ])
            ->getMock();

        return $threeDSecureCallbackManagementMock;
    }
}
