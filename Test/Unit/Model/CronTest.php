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
    private $cronModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory |\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionFactoryMock;

    /**
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderPaymentRepositoryMock;

    /**
     * @var Ebizmarts\SagePaySuite\Helper\Fraud|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fraudHelper;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->fraudHelper = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Fraud')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
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

        $this->orderCollectionFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\ResourceModel\Order\CollectionFactory')
            ->setMethods(["create", 'addFieldToFilter', 'load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderCollectionFactoryMock->method('create')->willReturnSelf();
        $this->orderCollectionFactoryMock->method('addFieldToFilter')->willReturnSelf();

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
                "resource" => $resourceMock,
                "orderCollectionFactory" => $this->orderCollectionFactoryMock,
                "transactionFactory" => $this->transactionFactoryMock,
                "orderPaymentRepository" => $this->orderPaymentRepositoryMock,
                "fraudHelper" => $this->fraudHelper
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testCancelPendingPaymentOrders()
    {
        $fraudModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fraudModelMock
            ->expects($this->once())
            ->method('getOrdersToCancel')
            ->willReturn([39, 139]);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(1));

        $orderMock1 = $this
        ->getMockBuilder('Magento\Sales\Model\Order')
        ->disableOriginalConstructor()
        ->getMock();
        $orderMock2 = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCollectionFactoryMock->method('load')->willReturn([$orderMock1, $orderMock2]);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cronModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Cron',
            [
                "config"                 => $this->configMock,
                "orderCollectionFactory" => $this->orderCollectionFactoryMock,
                "transactionFactory"     => $this->transactionFactoryMock,
                "orderPaymentRepository" => $this->orderPaymentRepositoryMock,
                "fraudHelper"            => $this->fraudHelper,
                "fraudModel"             => $fraudModelMock
            ]
        );

        $this->cronModel->cancelPendingPaymentOrders();
    }

    public function testCheckFraud()
    {
        $fraudModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fraudModelMock
            ->expects($this->once())
            ->method('getShadowPaidPaymentTransactions')
            ->willReturn([["transaction_id" => 67], ["transaction_id" => 389]]);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentRepositoryMock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($paymentMock));

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $this->fraudHelper->expects($this->exactly(2))
            ->method("processFraudInformation");

        $trnInstanceMock = $this
            ->getMockBuilder(\Magento\Sales\Api\Data\TransactionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trnInstanceMock->expects($this->exactly(2))->method('getPaymentId')->willReturn(1234);

        $trnRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Api\TransactionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trnRepoMock->expects($this->exactly(2))->method('get')->willReturn($trnInstanceMock);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cronModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Cron',
            [
                "config"                 => $this->configMock,
                "orderFactory"           => $this->orderCollectionFactoryMock,
                "transactionFactory"     => $this->transactionFactoryMock,
                "orderPaymentRepository" => $this->orderPaymentRepositoryMock,
                "fraudHelper"            => $this->fraudHelper,
                "fraudModel"             => $fraudModelMock,
                "transactionRepository"  => $trnRepoMock
            ]
        );

        $this->cronModel->checkFraud();
    }
}
