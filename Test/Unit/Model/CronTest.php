<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class CronTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Cron
     */
    protected $cronModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Reporting|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportingApiMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Sales\Model\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderFactoryMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transactionFactoryMock;

    /**
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderPaymentRepositoryMock;

    protected function setUp()
    {
        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->reportingApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Reporting')
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this
            ->getMockBuilder('Magento\Framework\DB\Select')
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('from')
            ->willReturnSelf();
        $selectMock->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $selectMock->expects($this->any())
            ->method('limit')
            ->willReturnSelf();

        $this->connectionMock = $this
            ->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($selectMock);

        $resourceMock = $this
            ->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connectionMock));

        $this->orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentRepositoryMock = $this
            ->getMockBuilder('Magento\Sales\Api\OrderPaymentRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cronModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Cron',
            [
                "config" => $this->configMock,
                "reportingApi" => $this->reportingApiMock,
                "resource" => $resourceMock,
                "orderFactory" => $this->orderFactoryMock,
                "transactionFactory" => $this->transactionFactoryMock,
                "orderPaymentRepository" => $this->orderPaymentRepositoryMock
            ]
        );
    }

    public function testCancelPendingPaymentOrders()
    {
        $this->connectionMock->expects($this->any())
            ->method('fetchAll')
            ->willReturn((object)[
                [
                    "entity_id" => 1
                ],
                [
                    "entity_id" => 2
                ]
            ]);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(1));


        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->exactly(2))
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $orderMock->expects($this->exactly(2))
            ->method('load')
            ->willReturnSelf();
        $orderMock->expects($this->exactly(1))
            ->method('cancel')
            ->willReturnSelf();

        $this->orderFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->will($this->returnValue($orderMock));

        $transactionMock1 = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();
        $transactionMock1->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $transactionMock1->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $transactionMock2 = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();
        $transactionMock2->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $transactionMock2->expects($this->once())
            ->method('getId')
            ->willReturn(0);

        $this->transactionFactoryMock->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue($transactionMock1));
        $this->transactionFactoryMock->expects($this->at(1))
            ->method('create')
            ->will($this->returnValue($transactionMock2));

        $this->cronModel->cancelPendingPaymentOrders();
    }

    public function testCheckFraud()
    {
        $this->connectionMock->expects($this->any())
            ->method('fetchAll')
            ->willReturn((object)[
                [
                    "transaction_id" => 1
                ],
                [
                    "transaction_id" => 2
                ]
            ]);

        $transactionMock1 = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();
        $transactionMock1->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $transactionMock2 = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();
        $transactionMock2->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $transactionMock2->expects($this->once())
            ->method('getTxnType')
            ->willReturn(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);
        $transactionMock2->expects($this->any())
            ->method('getIsTransactionClosed')
            ->willReturn(false);

        $this->transactionFactoryMock->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue($transactionMock1));
        $this->transactionFactoryMock->expects($this->at(1))
            ->method('create')
            ->will($this->returnValue($transactionMock2));

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentRepositoryMock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($paymentMock));

        $this->reportingApiMock->expects($this->at(0))
            ->method('getFraudScreenDetail')
            ->will($this->returnValue((object)[
                "errorcode" => "0000",
                "fraudscreenrecommendation" => \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT,
                "fraudid" => "12345",
                "fraudcode" => "765",
                "fraudcodedetail" => "Fraud card",
                "fraudprovidername" => "ReD",
                "rules" => "",

            ]));

        $this->reportingApiMock->expects($this->at(1))
            ->method('getFraudScreenDetail')
            ->will($this->returnValue((object)[
                "errorcode" => "0000",
                "fraudscreenrecommendation" => \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_OK,
                "fraudid" => "12345",
                "fraudcode" => "765",
                "fraudcodedetail" => "Fraud card",
                "fraudprovidername" => "ReD",
                "rules" => "",

            ]));

        $invoiceMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Invoice')
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects($this->once())
            ->method('register');
        $invoiceMock->expects($this->once())
            ->method('capture');

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('setStatus')
            ->with(\Magento\Sales\Model\Order::STATUS_FRAUD);
        $orderMock->expects($this->once())
            ->method('prepareInvoice')
            ->willReturn($invoiceMock);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);
        $paymentMock->expects($this->once())
            ->method('setIsFraudDetected')
            ->with(true);

        $this->configMock->expects($this->at(0))
            ->method('getAutoInvoiceFraudPassed')
            ->willReturn(false);
        $this->configMock->expects($this->at(1))
            ->method('getAutoInvoiceFraudPassed')
            ->willReturn(true);

        $this->cronModel->checkFraud();
    }
}
