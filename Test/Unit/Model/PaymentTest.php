<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Ebizmarts\SagePaySuite\Model\Payment;
use Magento\Sales\Model\Order\Payment as OrderPayment;

class PaymentTest extends \PHPUnit\Framework\TestCase
{

    public function testSetOrderStateAndStatusPayment()
    {
        $objectManagerHelper = new ObjectManager($this);

        /** @var Payment $sut */
        $sut = $objectManagerHelper->getObject(Payment::class);

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
        $objectManagerHelper = new ObjectManager($this);

        /** @var Payment $sut */
        $sut = $objectManagerHelper->getObject(Payment::class);

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
        $objectManagerHelper = new ObjectManager($this);

        /** @var Payment $sut */
        $sut = $objectManagerHelper->getObject(Payment::class);

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

}