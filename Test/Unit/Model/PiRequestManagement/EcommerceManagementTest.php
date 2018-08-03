<?php
/**
 * Copyright © 2018 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Model\PiRequestManagement\EcommerceManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Config\ClosedForAction;
use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerInterface;

class EcommerceManagementTest extends \PHPUnit\Framework\TestCase
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
        $objectManagerHelper = new ObjectManager($this);

        /** @var EcommerceManagement $sut */
        $sut = $this->objectManagerHelper->getObject(EcommerceManagement::class);

        $this->assertFalse($sut->getIsMotoTransaction());
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
        $quoteMock->expects($this->any())->method('reserveOrderId')->willReturnSelf();

        $requestDataMock = $this->makeMockDisabledConstructor(PiRequestManagerInterface::class);
        $requestDataMock->expects($this->any())->method('getPaymentAction')->willReturn($paymentAction);

        $payResultMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface::class);
        $payResultMock->expects($this->any())->method('getStatusCode')->willReturn(Config::SUCCESS_STATUS);

        $piRestApiMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class);
        $piRestApiMock->expects($this->once())->method('capture')->willReturn($payResultMock);

        $sageCardTypeMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\Config\SagePayCardType::class);
        $sageCardTypeMock->expects($this->once())->method('convert');

        $piRequestMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\PiRequest::class);
        $piRequestMock->expects($this->exactly(2))->method('setCart')->willReturnSelf();
        $piRequestMock->expects($this->exactly(2))->method('setMerchantSessionKey')->willReturnSelf();
        $piRequestMock->expects($this->exactly(2))->method('setCardIdentifier')->willReturnSelf();
        $piRequestMock->expects($this->exactly(2))->method('setVendorTxCode')->willReturnSelf();
        $piRequestMock->expects($this->exactly(2))->method('setIsMoto')->willReturnSelf();
        $piRequestMock->expects($this->exactly(2))->method('getRequestData')->willReturn(
            ['transactionType' => $paymentAction]
        );

        $suiteHelperMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Helper\Data::class);

        $piResultMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Api\Data\PiResultInterface::class);
        $piResultMock->expects($this->once())->method('setSuccess')->with(true);

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
        $paymentMock->expects($this->any())->method('save')->willReturnSelf();
        $paymentMock->expects($this->any())->method('getMethodInstance')->willReturn($methodInstanceMock);

        $quoteMock->expects($this->any())->method('getPayment')->willReturn($paymentMock);

        $orderMock = $this->makeMockDisabledConstructor(\Magento\Sales\Model\Order::class);
        $orderMock->expects($this->any())->method('getPayment')->willReturn($paymentMock);
        $orderMock->expects($this->any())->method('place')->willReturnSelf();
        $orderMock->expects($this->any())->method('getId')->willReturn(self::TEST_ORDER_NUMBER);

        $checkoutHelperMock->expects($this->once())->method('placeOrder')->willReturn($orderMock);

        $loggerMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class);

        $actionFactoryMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $actionFactoryMock->expects($this->any())->method('create')->willReturn(
            new ClosedForAction($paymentAction)
        );

        $transactionFactoryMock = $this->makeMockDisabledConstructor('Magento\Sales\Model\Order\Payment\TransactionFactory');
        $transactionFactoryMock->expects($this->any())->method('create')->willReturn(
            $this->makeMockDisabledConstructor(\Magento\Sales\Model\Order\Payment\Transaction::class)
        );


        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
        ->disableOriginalConstructor()
        ->setMethods(
            [
                'setData',
                'clearHelperData',
                'setLastQuoteId',
                'setLastSuccessQuoteId',
                'setLastOrderId',
                'setLastRealOrderId',
                'setLastOrderStatus',
            ]
        )
        ->getMock();
        $checkoutSessionMock->expects($this->once())->method('setData')
            ->with(
                $this->equalTo('sagepaysuite_presaved_order_pending_payment'),
                $this->equalTo(self::TEST_ORDER_NUMBER)
            );
        $checkoutSessionMock->expects($this->once())->method('clearHelperData');
        $checkoutSessionMock->expects($this->once())->method('setLastQuoteId');
        $checkoutSessionMock->expects($this->once())->method('setLastSuccessQuoteId');
        $checkoutSessionMock->expects($this->once())->method('setLastOrderId');
        $checkoutSessionMock->expects($this->once())->method('setLastRealOrderId');
        $checkoutSessionMock->expects($this->once())->method('setLastOrderStatus');

        /** @var MotoManagement $sut */
        $sut = $this->objectManagerHelper->getObject(
            EcommerceManagement::class,
            [
                'checkoutHelper' => $checkoutHelperMock,
                'piRestApi' => $piRestApiMock,
                'ccConvert' => $sageCardTypeMock,
                'piRequest' => $piRequestMock,
                'suiteHelper' => $suiteHelperMock,
                'result' => $piResultMock,
                'suiteLogger' => $loggerMock,
                'actionFactory' => $actionFactoryMock,
                'transactionFactory' => $transactionFactoryMock,
                'checkoutSession' => $checkoutSessionMock
            ]
        );

        $sut->setQuote($quoteMock);
        $sut->setRequestData($requestDataMock);

        $sut->placeOrder();
    }

    public function placeOrder()
    {
        return [
            'Payment payment action' => [Config::ACTION_PAYMENT_PI, 1, 0],
            'Deferred payment action' => [Config::ACTION_DEFER_PI, 0, 1,]
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