<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 4/12/18
 * Time: 2:01 PM
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerInterface;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Config\ClosedForAction;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\MotoManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class MotoManagementTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    private $objectManagerHelper;

    const TEST_ORDER_NUMBER = 7832;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManager($this);
    }

    public function testIsMotoTransaction()
    {
        /** @var MotoManagement $sut */
        $sut = $this->objectManagerHelper->getObject(MotoManagement::class);

        $this->assertTrue($sut->getIsMotoTransaction());
    }

    /**
     * @param string $paymentAction @see \Ebizmarts\SagePaySuite\Model\Config
     * @param integer $expectsMarkInitialized
     * @param integer $expectsTransactionClosed
     * @dataProvider placeOrder
     */
    public function testPlaceOrder($paymentAction, $expectsMarkInitialized, $expectsTransactionClosed)
    {
        $checkoutHelperMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Helper\Checkout::class);

        $quoteMock = $this->makeMockDisabledConstructor(\Magento\Quote\Model\Quote::class);
        $quoteMock->expects($this->exactly(2))->method('collectTotals')->willReturnSelf();
        $quoteMock->expects($this->once())->method('reserveOrderId')->willReturnSelf();

        $requestDataMock = $this->makeMockDisabledConstructor(PiRequestManagerInterface::class);
        $requestDataMock->expects($this->any())->method('getPaymentAction')->willReturn($paymentAction);

        $payResultMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface::class);
        $payResultMock->expects($this->any())->method('getStatusCode')->willReturn(Config::SUCCESS_STATUS);

        $piRestApiMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class);
        $piRestApiMock->expects($this->once())->method('capture')->willReturn($payResultMock);

        $sageCardTypeMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\Config\SagePayCardType::class);
        $sageCardTypeMock->expects($this->once())->method('convert');

        $piRequestMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\PiRequest::class);
        $piRequestMock->expects($this->once())->method('setCart')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setMerchantSessionKey')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setCardIdentifier')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setVendorTxCode')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setIsMoto')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('getRequestData')->willReturn([]);

        $suiteHelperMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Helper\Data::class);

        $piResultMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Api\Data\PiResultInterface::class);
        $piResultMock->expects($this->once())->method('setSuccess')->with(true);
        $piResultMock->expects($this->once())->method('setResponse');

        $methodInstanceMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\PI::class);
        $methodInstanceMock->expects($this->exactly($expectsMarkInitialized))->method('markAsInitialized');

        $paymentMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->setMethods(
                [
                    'setMethod',
                    'setTransactionId',
                    'setAdditionalInformation',
                    'setCcLast4',
                    'setCcExpMonth',
                    'setCcExpYear',
                    'setCcType',
                    'setIsTransactionClosed',
                    'save',
                    'getMethodInstance'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())->method('setMethod')->willReturnSelf();
        $paymentMock->expects($this->any())->method('setTransactionId')->willReturnSelf();
        $paymentMock->expects($this->any())->method('setAdditionalInformation')->willReturnSelf();
        $paymentMock->expects($this->any())->method('setCcLast4')->willReturnSelf();
        $paymentMock->expects($this->any())->method('setCcExpMonth')->willReturnSelf();
        $paymentMock->expects($this->any())->method('setCcExpYear')->willReturnSelf();
        $paymentMock->expects($this->any())->method('setCcType')->willReturnSelf();
        $paymentMock->expects($this->exactly($expectsTransactionClosed))->method('setIsTransactionClosed')->willReturnSelf();
        $paymentMock->expects($this->any())->method('save')->willReturnSelf();
        $paymentMock->expects($this->any())->method('getMethodInstance')->willReturn($methodInstanceMock);

        $orderMock = $this->makeMockDisabledConstructor(\Magento\Sales\Model\Order::class);
        $orderMock->expects($this->any())->method('getPayment')->willReturn($paymentMock);
        $orderMock->expects($this->any())->method('place')->willReturnSelf();
        $orderMock->expects($this->any())->method('getId')->willReturn(self::TEST_ORDER_NUMBER);

        $motoOrderCreateModelMock = $this->getMockBuilder(\Magento\Sales\Model\AdminOrder\Create::class)
            ->setMethods(
                [
                    'setIsValidate',
                    'importPostData',
                    'setSendConfirmation',
                    'createOrder',
                    'getQuote'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $motoOrderCreateModelMock->expects($this->once())->method('setIsValidate')->with(true)->willReturnSelf();
        $motoOrderCreateModelMock->expects($this->once())->method('importPostData')->willReturnSelf();
        $motoOrderCreateModelMock->expects($this->any())->method('setSendConfirmation')->with(0)->willReturnSelf();
        $motoOrderCreateModelMock->expects($this->once())->method('createOrder')->willReturn($orderMock);
        $motoOrderCreateModelMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);

        $objectManagerMock = $this->makeMockDisabledConstructor(\Magento\Framework\ObjectManagerInterface::class);
        $objectManagerMock->expects($this->exactly(2))->method('get')->with('Magento\Sales\Model\AdminOrder\Create')
            ->willReturn($motoOrderCreateModelMock);

        $requestMock = $this->makeMockDisabledConstructor(\Magento\Framework\App\Request\Http::class);
        $requestMock->expects($this->exactly(2))->method('getPost')
            ->withConsecutive(['order'], ['payment'])
            ->willReturnOnConsecutiveCalls([], []);

        $urlMock = $this->makeMockDisabledConstructor(\Magento\Backend\Model\UrlInterface::class);
        $urlMock->expects($this->once())->method('getUrl')->with('sales/order/view', ['order_id' => self::TEST_ORDER_NUMBER]);

        $loggerMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class);

        $emailSenderMock = $this->makeMockDisabledConstructor(\Magento\Sales\Model\AdminOrder\EmailSender::class);
        $emailSenderMock->expects($this->once())->method('send');

        $actionFactoryMock = $this->makeMockDisabledConstructor('Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory');
        $actionFactoryMock->expects($this->any())->method('create')->willReturn(
            new ClosedForAction($paymentAction)
        );

        $transactionFactoryMock = $this->makeMockDisabledConstructor('Magento\Sales\Model\Order\Payment\TransactionFactory');
        $transactionFactoryMock->expects($this->any())->method('create')->willReturn(
            $this->makeMockDisabledConstructor(\Magento\Sales\Model\Order\Payment\Transaction::class)
        );

        /** @var MotoManagement $sut */
        $sut = $this->objectManagerHelper->getObject(
            MotoManagement::class,
            [
                'checkoutHelper' => $checkoutHelperMock,
                'piRestApi' => $piRestApiMock,
                'ccConvert' => $sageCardTypeMock,
                'piRequest' => $piRequestMock,
                'suiteHelper' => $suiteHelperMock,
                'result' => $piResultMock,
                'objectManager' => $objectManagerMock,
                'httpRequest' => $requestMock,
                'backendUrl' => $urlMock,
                'suiteLogger' => $loggerMock,
                'emailSender' => $emailSenderMock,
                'actionFactory' => $actionFactoryMock,
                'transactionFactory' => $transactionFactoryMock
            ]
        );

        $sut->setQuote($quoteMock);
        $sut->setRequestData($requestDataMock);

        $sut->placeOrder();
    }

    public function placeOrder()
    {
        return [
            [Config::ACTION_PAYMENT_PI, 1, 0],
            [Config::ACTION_DEFER_PI, 0, 1]
        ];
    }

    /**
     * @param string $class
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeMockDisabledConstructor($class)
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

}