<?php
/**
 * Copyright © 2018 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Api\Data\PiResultInterface;
use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface;
use Ebizmarts\SagePaySuite\Helper\Checkout;
use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\Api\PIRest;
use Ebizmarts\SagePaySuite\Model\Config\SagePayCardType;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\PiRequest;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\EcommerceManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Config\ClosedForAction;
use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteValidator;
use Magento\Sales\Model\Order;

class EcommerceManagementTest extends \PHPUnit_Framework_TestCase
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
        /** @var EcommerceManagement $sut */
        $sut = $this->objectManagerHelper->getObject(EcommerceManagement::class);

        $this->assertFalse($sut->getIsMotoTransaction());
    }

    public function testPlaceOrder()
    {
        $paymentAction = Config::ACTION_PAYMENT_PI;
        $expectsMarkInitialized = 1;

        $checkoutHelperMock = $this->makeMockDisabledConstructor(Checkout::class);

        $quoteMock = $this->makeMockDisabledConstructor(Quote::class);
        $quoteMock->expects($this->once())->method('collectTotals')->willReturnSelf();
        $quoteMock->expects($this->once())->method('reserveOrderId')->willReturnSelf();

        $requestDataMock = $this->makeMockDisabledConstructor(PiRequestManagerInterface::class);
        $requestDataMock->expects($this->any())->method('getPaymentAction')->willReturn($paymentAction);

        $payResultMock = $this->makeMockDisabledConstructor(PiTransactionResultInterface::class);
        $payResultMock->expects($this->any())->method('getStatusCode')->willReturn(Config::SUCCESS_STATUS);

        $piRestApiMock = $this->makeMockDisabledConstructor(PIRest::class);
        $piRestApiMock->expects($this->once())->method('capture')->willReturn($payResultMock);

        $sageCardTypeMock = $this->makeMockDisabledConstructor(SagePayCardType::class);
        $sageCardTypeMock->expects($this->once())->method('convert');

        $piRequestMock = $this->makeMockDisabledConstructor(PiRequest::class);
        $piRequestMock->expects($this->once())->method('setCart')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setMerchantSessionKey')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setCardIdentifier')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setVendorTxCode')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setIsMoto')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('getRequestData')->willReturn(
            ['transactionType' => $paymentAction]
        );

        $suiteHelperMock = $this->makeMockDisabledConstructor(Data::class);

        $piResultMock = $this->makeMockDisabledConstructor(PiResultInterface::class);
        $piResultMock->expects($this->once())->method('setSuccess')->with(true);
        $piResultMock->expects($this->once())->method('getSuccess')->willReturn(true);

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

        $orderMock = $this->makeMockDisabledConstructor(Order::class);
        $orderMock->expects($this->any())->method('getPayment')->willReturn($paymentMock);
        $orderMock->expects($this->any())->method('place')->willReturnSelf();
        $orderMock->expects($this->any())->method('getId')->willReturn(self::TEST_ORDER_NUMBER);

        $checkoutHelperMock->expects($this->once())->method('placeOrder')->willReturn($orderMock);

        $loggerMock = $this->makeMockDisabledConstructor(Logger::class);

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

        $quoteValidatorMock = $this->getMockBuilder(QuoteValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteValidatorMock->expects($this->once())->method('validateBeforeSubmit')->with($quoteMock)->willReturnSelf();

        /** @var EcommerceManagement $sut */
        $sut = $this->objectManagerHelper->getObject(
            EcommerceManagement::class,
            [
                'checkoutHelper'     => $checkoutHelperMock,
                'piRestApi'          => $piRestApiMock,
                'ccConvert'          => $sageCardTypeMock,
                'piRequest'          => $piRequestMock,
                'suiteHelper'        => $suiteHelperMock,
                'result'             => $piResultMock,
                'sagePaySuiteLogger' => $loggerMock,
                'actionFactory'      => $actionFactoryMock,
                'transactionFactory' => $transactionFactoryMock,
                'checkoutSession'    => $checkoutSessionMock,
                'quoteValidator'     => $quoteValidatorMock
            ]
        );

        $sut->setQuote($quoteMock);
        $sut->setRequestData($requestDataMock);

        $result = $sut->placeOrder();
        $this->assertTrue($result->getSuccess());
    }

    public function testPlaceOrderReservedOrderIdAlreadySet()
    {
        $checkoutHelperMock = $this->makeMockDisabledConstructor(Checkout::class);

        $quoteMock = $this->makeMockDisabledConstructor(Quote::class);
        $quoteMock->expects($this->once())->method('collectTotals')->willReturnSelf();
        $quoteMock->expects($this->exactly(3))->method('getReservedOrderId')->willReturn('000000083');
        $quoteMock->expects($this->never())->method('reserveOrderId');

        $requestDataMock = $this->makeMockDisabledConstructor(PiRequestManagerInterface::class);
        $requestDataMock->expects($this->any())->method('getPaymentAction')->willReturn(Config::ACTION_PAYMENT_PI);

        $payResultMock = $this->makeMockDisabledConstructor(PiTransactionResultInterface::class);
        $payResultMock->expects($this->any())->method('getStatusCode')->willReturn(Config::SUCCESS_STATUS);

        $piRestApiMock = $this->makeMockDisabledConstructor(PIRest::class);
        $piRestApiMock->expects($this->once())->method('capture')->willReturn($payResultMock);

        $sageCardTypeMock = $this->makeMockDisabledConstructor(SagePayCardType::class);
        $sageCardTypeMock->expects($this->once())->method('convert');

        $piRequestMock = $this->makeMockDisabledConstructor(PiRequest::class);
        $piRequestMock->expects($this->once())->method('setCart')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setMerchantSessionKey')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setCardIdentifier')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setVendorTxCode')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setIsMoto')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('getRequestData')->willReturn(
            ['transactionType' => Config::ACTION_PAYMENT_PI]
        );

        $suiteHelperMock = $this->makeMockDisabledConstructor(Data::class);

        $piResultMock = $this->makeMockDisabledConstructor(PiResultInterface::class);
        $piResultMock->expects($this->once())->method('setSuccess')->with(true);
        $piResultMock->expects($this->once())->method('getSuccess')->willReturn(true);

        $methodInstanceMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\PI::class);
        $methodInstanceMock->expects($this->once())->method('markAsInitialized');

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

        $orderMock = $this->makeMockDisabledConstructor(Order::class);
        $orderMock->expects($this->any())->method('getPayment')->willReturn($paymentMock);
        $orderMock->expects($this->any())->method('place')->willReturnSelf();
        $orderMock->expects($this->any())->method('getId')->willReturn(self::TEST_ORDER_NUMBER);

        $checkoutHelperMock->expects($this->once())->method('placeOrder')->willReturn($orderMock);

        $loggerMock = $this->makeMockDisabledConstructor(Logger::class);

        $actionFactoryMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $actionFactoryMock->expects($this->any())->method('create')->willReturn(
            new ClosedForAction(Config::ACTION_PAYMENT_PI)
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

        $quoteValidatorMock = $this->getMockBuilder(QuoteValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteValidatorMock->expects($this->once())->method('validateBeforeSubmit')->with($quoteMock)->willReturnSelf();

        /** @var EcommerceManagement $sut */
        $sut = $this->objectManagerHelper->getObject(
            EcommerceManagement::class,
            [
                'checkoutHelper'     => $checkoutHelperMock,
                'piRestApi'          => $piRestApiMock,
                'ccConvert'          => $sageCardTypeMock,
                'piRequest'          => $piRequestMock,
                'suiteHelper'        => $suiteHelperMock,
                'result'             => $piResultMock,
                'sagePaySuiteLogger' => $loggerMock,
                'actionFactory'      => $actionFactoryMock,
                'transactionFactory' => $transactionFactoryMock,
                'checkoutSession'    => $checkoutSessionMock,
                'quoteValidator'     => $quoteValidatorMock
            ]
        );

        $sut->setQuote($quoteMock);
        $sut->setRequestData($requestDataMock);

        $result = $sut->placeOrder();
        $this->assertTrue($result->getSuccess());
    }

    public function testPlaceOrderInvalidQuote()
    {
        $checkoutHelperMock = $this->makeMockDisabledConstructor(Checkout::class);

        $quoteMock = $this->makeMockDisabledConstructor(Quote::class);

        $requestDataMock = $this->makeMockDisabledConstructor(PiRequestManagerInterface::class);

        $piRestApiMock = $this->makeMockDisabledConstructor(PIRest::class);
        $piRestApiMock->expects($this->never())->method('void');

        $sageCardTypeMock = $this->makeMockDisabledConstructor(SagePayCardType::class);

        $piRequestMock = $this->makeMockDisabledConstructor(PiRequest::class);

        $suiteHelperMock = $this->makeMockDisabledConstructor(Data::class);

        $piResultMock = $this->makeMockDisabledConstructor(PiResultInterface::class);
        $piResultMock->expects($this->once())->method('setSuccess')->with(false);
        $piResultMock->expects($this->once())->method('getSuccess')->willReturn(false);
        $piResultMock
            ->expects($this->once())
            ->method('setErrorMessage')
            ->with(
                new \Magento\Framework\Phrase('Something went wrong: %1', ['Please specify a shipping method.'])
            );

        $checkoutHelperMock->expects($this->never())->method('placeOrder');

        $loggerMock = $this->makeMockDisabledConstructor(Logger::class);
        $loggerMock->expects($this->once())->method('logException');

        $quoteValidatorMock = $this->getMockBuilder(QuoteValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteValidatorMock
            ->expects($this->once())
            ->method('validateBeforeSubmit')
            ->willThrowException($this->makeNoShippingMethodException());

        /** @var EcommerceManagement $sut */
        $sut = $this->objectManagerHelper->getObject(
            EcommerceManagement::class,
            [
                'checkoutHelper'     => $checkoutHelperMock,
                'piRestApi'          => $piRestApiMock,
                'ccConvert'          => $sageCardTypeMock,
                'piRequest'          => $piRequestMock,
                'suiteHelper'        => $suiteHelperMock,
                'result'             => $piResultMock,
                'sagePaySuiteLogger' => $loggerMock,
                'quoteValidator'     => $quoteValidatorMock
            ]
        );

        $sut->setQuote($quoteMock);
        $sut->setRequestData($requestDataMock);

        /** @var \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface $result */
        $result = $sut->placeOrder();
        $this->assertFalse($result->getSuccess());
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

    /**
     * @return \Magento\Framework\Exception\LocalizedException
     */
    private function makeNoShippingMethodException()
    {
        return new \Magento\Framework\Exception\LocalizedException(
            new \Magento\Framework\Phrase('Please specify a shipping method.')
        );
    }

}