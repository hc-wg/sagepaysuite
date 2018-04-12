<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Api\SagePayData\PiInstructionResponse;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface;
use Ebizmarts\SagePaySuite\Model\Api\Pi;
use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Ebizmarts\SagePaySuite\Model\Payment;
use Magento\Sales\Model\Order\Payment as OrderPayment;

class PaymentTest extends \PHPUnit\Framework\TestCase
{

    public function testSetOrderStateAndStatusPayment()
    {
        /** @var Payment $sut */
        $sut = $this->makeObjectManager()->getObject(Payment::class);

        $paymentMock = $this->getMockBuilder(OrderPayment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->never())->method('getLastTransId');

        $stateObjectMock = $this->makeStateObjectAssertStateStatus('pending_payment', 'pending_payment');

        $sut->setOrderStateAndStatus($paymentMock, 'PAYMENT', $stateObjectMock);
    }

    /**
     * @dataProvider deferredAuthenticateOrderStatusDataProvider
     */
    public function testSetOrderStateAndStatusDeferredAuthenticateNoTransactionId($paymentAction)
    {
        /** @var Payment $sut */
        $sut = $this->makeObjectManager()->getObject(Payment::class);

        $paymentMock = $this->getMockBuilder(OrderPayment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())->method('getLastTransId')->willReturn(null);

        $stateObjectMock = $this->makeStateObjectAssertStateStatus('pending_payment', 'pending_payment');

        $sut->setOrderStateAndStatus($paymentMock, $paymentAction, $stateObjectMock);
    }

    /**
     * @dataProvider deferredAuthenticateOrderStatusDataProvider
     */
    public function testSetOrderStateAndStatusDeferredAuthenticate($paymentAction)
    {
        /** @var Payment $sut */
        $sut = $this->makeObjectManager()->getObject(Payment::class);

        $paymentMock = $this->getMockBuilder(OrderPayment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())->method('getLastTransId')->willReturn(9812);

        $stateObjectMock = $this->makeStateObjectAssertStateStatus('new', 'pending');

        $sut->setOrderStateAndStatus($paymentMock, $paymentAction, $stateObjectMock);
    }

    public function deferredAuthenticateOrderStatusDataProvider()
    {
        return [
            ['DEFERRED'], ['AUTHENTICATE']
        ];
    }

    private function makeStateObjectAssertStateStatus($expectedState, $expectedStatus)
    {
        $stateObjectMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['setState', 'setStatus'])
            ->getMock();
        $stateObjectMock->expects($this->once())->method('setState')->with($expectedState);
        $stateObjectMock->expects($this->once())->method('setStatus')->with($expectedStatus);

        return $stateObjectMock;
    }

    public function testCaptureDeferredPiTransaction()
    {
        $testAmount  = 377.68;
        $testVpsTxId = 'ABCD-1234';

        $resultMock = $this->getMockBuilder(PiInstructionResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $piApiMock = $this->makePiApiMock($testVpsTxId, $testAmount, $resultMock);

        /** @var Payment $sut */
        $sut = $this->makeObjectManager()->getObject(
            Payment::class,
            [
                'config' => $this->makeConfigMockPiDeferredAction()
            ]
        );

        $sut->setApi($piApiMock);

        $orderMock = $this->makeOrderMockPendingState();

        $paymentMock = $this->makePaymentMock($orderMock, $testVpsTxId);
        $paymentMock->expects($this->exactly(3))->method('getLastTransId')->willReturn($testVpsTxId);
        $paymentMock->expects($this->once())->method('setParentTransactionId')->with($testVpsTxId);
        $paymentMock->expects($this->once())->method('getParentTransactionId')->willReturn($testVpsTxId);
        $paymentMock->expects($this->once())->method('setTransactionId')->with($testVpsTxId);

        $sut->capture($paymentMock, $testAmount);
    }

    public function testCaptureDeferredReleasedPiTransaction()
    {
        $testAmount  = 377.68;
        $testVpsTxId = 'ABCD-1234';

        $resultMock = $this->getMockBuilder(PiTransactionResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock->expects($this->once())->method('getTransactionId')->willReturn('EFAB-0987');

        $piApiMock = $this->makePiApiMock($testVpsTxId, $testAmount, $resultMock);

        /** @var Payment $sut */
        $sut = $this->makeObjectManager()->getObject(
            Payment::class,
            [
                'config' => $this->makeConfigMockPiDeferredAction()
            ]
        );

        $sut->setApi($piApiMock);

        $orderMock = $this->makeOrderMockPendingState();

        $paymentMock = $this->makePaymentMock($orderMock, $testVpsTxId);
        $paymentMock->expects($this->once())->method('getLastTransId')->willReturn($testVpsTxId);
        $paymentMock->expects($this->once())->method('setParentTransactionId')->with($testVpsTxId);
        $paymentMock->expects($this->exactly(2))->method('getParentTransactionId')->willReturn($testVpsTxId);
        $paymentMock->expects($this->once())->method('setTransactionId')->with('EFAB-0987');

        $sut->capture($paymentMock, $testAmount);
    }

    /**
     * @return ObjectManager
     */
    private function makeObjectManager(): ObjectManager
    {
        return new ObjectManager($this);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeConfigMockPiDeferredAction()
    {
        $configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $configMock->expects($this->once())->method('getSagepayPaymentAction')->willReturn('Deferred');

        return $configMock;
    }

    /**
     * @param $testVpsTxId
     * @param $testAmount
     * @param $resultMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makePiApiMock($testVpsTxId, $testAmount, $resultMock)
    {
        $piApiMock = $this->getMockBuilder(Pi::class)->disableOriginalConstructor()->getMock();

        $piApiMock->expects($this->once())->method('captureDeferredTransaction')->with($testVpsTxId,
                $testAmount)->willReturn($resultMock);

        return $piApiMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeOrderMockPendingState()
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)->disableOriginalConstructor()->getMock();
        $orderMock->expects($this->once())->method('getState')->willReturn('pending');

        return $orderMock;
    }

    /**
     * @param $orderMock
     * @param $testVpsTxId
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makePaymentMock($orderMock, $testVpsTxId)
    {
        $paymentMock = $this->getMockBuilder(OrderPayment::class)->disableOriginalConstructor()->getMock();
        $paymentMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        return $paymentMock;
    }

}