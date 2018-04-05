<?php
declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;

class OrderUpdateOnCallbackTest extends \PHPUnit\Framework\TestCase
{
    /** @var Config|\PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var OrderSender|\PHPUnit_Framework_MockObject_MockObject */
    private $orderEmailSenderMock;

    /** @var \Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $actionFactoryMock;

    /** @var \Magento\Sales\Model\Order\Payment\TransactionFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $transactionFactoryMock;

    /** @var Repository|\PHPUnit_Framework_MockObject_MockObject */
    private $transactionRepositoryMock;

    /** @var ObjectManagerHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $objectManagerHelper;

    public function setUp()
    {
        $this->configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->orderEmailSenderMock       = $this->getMockBuilder(OrderSender::class)->disableOriginalConstructor()->getMock();
        $this->actionFactoryMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory')->disableOriginalConstructor()->getMock();
        $this->transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')->disableOriginalConstructor()->getMock();
        $this->transactionRepositoryMock = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
    }

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid order. Cant confirm payment.
     */
    public function testConfirmPaymentNoOrderSet()
    {
        /** @var OrderUpdateOnCallback $sut */
        $sut = $this->objectManagerHelper->getObject(
            OrderUpdateOnCallback::class,
            [
                'config' => $this->configMock,
                'orderEmailSender' => $this->orderEmailSenderMock,
                'actionFactory' => $this->actionFactoryMock,
                'transactionFactory' => $this->transactionFactoryMock,
                'transactionRepository' => $this->transactionRepositoryMock,
            ]
        );

        $sut->confirmPayment("test-transaction-id");
    }

    /**
     * @expectedException \Magento\Framework\Exception\AlreadyExistsException
     * @expectedExceptionMessage Transaction already exists.
     */
    public function testConfirmPaymentGatewayRetry()
    {
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())->method('getId')->willReturn(564);

        $this->transactionRepositoryMock->expects($this->once())->method('getByTransactionId')
            ->with("test-transaction-id", 564, 123)->willReturn(
                $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock()
            );

        /** @var OrderUpdateOnCallback $sut */
        $sut = $this->objectManagerHelper->getObject(
            OrderUpdateOnCallback::class,
            [
                'config' => $this->configMock,
                'orderEmailSender' => $this->orderEmailSenderMock,
                'actionFactory' => $this->actionFactoryMock,
                'transactionFactory' => $this->transactionFactoryMock,
                'transactionRepository' => $this->transactionRepositoryMock,
            ]
        );

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getId')->willReturn(123);
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);

        $sut->setOrder($orderMock);

        $sut->confirmPayment("test-transaction-id");
    }

}