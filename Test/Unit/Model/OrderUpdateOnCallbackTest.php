<?php
declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;

class OrderUpdateOnCallbackTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid order. Cant confirm payment.
     */
    public function testConfirmPayment()
    {
        $configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $orderEmailSenderMock = $this->getMockBuilder(OrderSender::class)->disableOriginalConstructor()
            ->getMock();
        $actionFactoryMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory')
            ->disableOriginalConstructor()->getMock();
        $transactionFactoryMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->disableOriginalConstructor()->getMock();
        $transactionRepositoryMock = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);

        /** @var OrderUpdateOnCallback $sut */
        $sut = $objectManagerHelper->getObject(
            OrderUpdateOnCallback::class,
            [
                'config' => $configMock,
                'orderEmailSender' => $orderEmailSenderMock,
                'actionFactory' => $actionFactoryMock,
                'transactionFactory' => $transactionFactoryMock,
                'transactionRepository' => $transactionRepositoryMock,
            ]
        );

        $sut->confirmPayment("test-transaction-id");
    }

}