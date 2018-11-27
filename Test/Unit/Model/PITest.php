<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Ebizmarts\SagePaySuite\Model\Api\Reporting;
use Ebizmarts\SagePaySuite\Model\PI;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Ebizmarts\SagePaySuite\Model\Payment as SagePayPayment;

class PITest extends \PHPUnit\Framework\TestCase
{
    private $objectManagerHelper;
    private $suiteHelperMock;
    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var PI
     */
    private $piModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sharedApiMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    // @codingStandardsIgnoreStart
    const SUCCESSFULLY_AUTH_TRANSACTION = 16;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManager($this);

        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Shared')
            ->disableOriginalConstructor()
            ->getMock();

        $this->suiteHelperMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->suiteHelperMock->expects($this->any())
            ->method('clearTransactionId')
            ->will($this->returnValue(self::TEST_VPSTXID));

        $this->piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config" => $this->configMock,
                'suiteHelper' => $this->suiteHelperMock,
                "sharedApi" => $this->sharedApiMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testMarkAsInitialized()
    {
        $this->piModel->markAsInitialized();
        $this->assertEquals(
            false,
            $this->piModel->isInitializeNeeded()
        );
    }

    public function testRefund()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn("1000001");
        $orderMock->expects($this->once())
            ->method('getOrderCurrencyCode')
            ->willReturn('GBP');

        $paymentMock = $this->makePaymentMockForInitialize($orderMock);
        $paymentMock->expects($this->once())
            ->method('setIsTransactionClosed')
            ->with(1);
        $paymentMock->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with(1);

        $piRestApiMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->suiteHelperMock
            ->expects($this->once())
            ->method('generateVendorTxCode')
            ->willReturn('R1000001');

        $returnMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTransactionId'])
            ->getMock();
        $returnMock->expects($this->once())
            ->method('getTransactionId')
            ->willReturn('a');

        $piRestApiMock
            ->expects($this->once())
            ->method('refund')
            ->with(
                'R1000001',
                self::TEST_VPSTXID,
                10000,
                'GBP',
                'Magento backend refund.'
            )
        ->willReturn($returnMock);

        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config"      => $this->configMock,
                "suiteHelper" => $this->suiteHelperMock,
                "pirestapi"   => $piRestApiMock
            ]
        );

        $piModel->refund($paymentMock, 100);
    }

    public function testRefundERROR()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn("1000001");
        $orderMock->expects($this->once())
            ->method('getOrderCurrencyCode')
            ->willReturn('GBP');

        $paymentMock = $this->makePaymentMockForInitialize($orderMock);

        $this->suiteHelperMock
            ->expects($this->once())
            ->method('generateVendorTxCode')
            ->willReturn('R1000001');
        $return = new \stdClass();
        $return->transactionId = 'a';
        $piRestApiMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRestApiMock
            ->expects($this->once())
            ->method('refund')
            ->with(
                'R1000001',
                self::TEST_VPSTXID,
                10000,
                'GBP',
                'Magento backend refund.'
            )
            ->willThrowException(new \Exception("Error in Refunding"));

        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config"      => $this->configMock,
                "suiteHelper" => $this->suiteHelperMock,
                "pirestapi"   => $piRestApiMock
            ]
        );

        $response = "";
        try {
            $piModel->refund($paymentMock, 100);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = $e->getMessage();
        }

        $this->assertEquals(
            'There was an error refunding Sage Pay transaction ' . self::TEST_VPSTXID . ': Error in Refunding',
            $response
        );
    }

    public function testRefundApiError()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn("1000001");
        $orderMock->expects($this->once())
            ->method('getOrderCurrencyCode')
            ->willReturn('GBP');

        $this->suiteHelperMock
            ->expects($this->once())
            ->method('generateVendorTxCode')
            ->willReturn('R1000001');

        $paymentMock = $this->makePaymentMockForInitialize($orderMock);

        $return = new \stdClass();
        $return->transactionId = 'a';
        $piRestApiMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRestApiMock
            ->expects($this->once())
            ->method('refund')
            ->with(
                'R1000001',
                self::TEST_VPSTXID,
                10000,
                'GBP',
                'Magento backend refund.'
            )
            ->willThrowException(
                new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
                    new \Magento\Framework\Phrase("The Transaction has already been Refunded.")
                )
            );

        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config"      => $this->configMock,
                "suiteHelper" => $this->suiteHelperMock,
                "pirestapi"   => $piRestApiMock
            ]
        );

        $response = "";
        try {
            $piModel->refund($paymentMock, 100);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = $e->getMessage();
        }

        $this->assertEquals(
            'There was an error refunding Sage Pay transaction ' .
            self::TEST_VPSTXID . ': The Transaction has already been Refunded.',
            $response
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Unable to VOID Sage Pay transaction
     */
    public function testVoidInvalidTransactionState()
    {
        $paymentMock = $this
            ->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->expects($this->any())
            ->method('getLastTransId')
            ->willReturn(self::TEST_VPSTXID);

        $return = new \stdClass();
        $return->transactionId = 'a';
        $piRestApiMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRestApiMock
            ->expects($this->once())
            ->method('void')
            ->with(self::TEST_VPSTXID)
            ->willThrowException(
                new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
                    new \Magento\Framework\Phrase("No transaction found."),
                    null,
                    '5004'
                )
            );

        /** @var PI $piModel */
        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config"      => $this->configMock,
                "suiteHelper" => $this->suiteHelperMock,
                "pirestapi"   => $piRestApiMock
            ]
        );

        $piModel->void($paymentMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Unable to VOID Sage Pay transaction
     */
    public function testVoidException()
    {
        $paymentMock = $this
            ->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->expects($this->once())
            ->method('getLastTransId')
            ->willReturn(self::TEST_VPSTXID);

        $exception = new \Magento\Framework\Exception\LocalizedException(
            __("No transaction found.")
        );
        $piRestApiMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRestApiMock
            ->expects($this->once())
            ->method('void')
            ->with(self::TEST_VPSTXID)
            ->willThrowException($exception);

        /** @var PI $piModel */
        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config"      => $this->configMock,
                "suiteHelper" => $this->suiteHelperMock,
                "pirestapi"   => $piRestApiMock
            ]
        );

        $piModel->void($paymentMock);
    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     * @expectedExceptionMessage No transaction found.
     */
    public function testVoidException2()
    {
        $paymentMock = $this
            ->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->expects($this->once())
            ->method('getLastTransId')
            ->willReturn(self::TEST_VPSTXID);

        $piRestApiMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRestApiMock
            ->expects($this->once())
            ->method('void')
            ->with(self::TEST_VPSTXID)
            ->willThrowException(
                new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
                    new \Magento\Framework\Phrase("No transaction found.")
                )
            );

        /** @var PI $piModel */
        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "pirestapi"   => $piRestApiMock
            ]
        );

        $piModel->void($paymentMock);
    }

    public function testCancel()
    {
        $orderMock = $this->makeOrderMockWithStoreId();

        $reportingApiMock = $this->getMockBuilder(Reporting::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reportingApiMock
            ->expects($this->once())
            ->method("getTransactionDetails")->willReturn($this->makeReportingResult());

        $paymentMock = $this
            ->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $paymentMock
            ->expects($this->once())
            ->method('getLastTransId')
            ->willReturn(self::TEST_VPSTXID);

        $piRestApiMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRestApiMock
            ->expects($this->once())
            ->method('void')
            ->with(self::TEST_VPSTXID);

        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "pirestapi"    => $piRestApiMock,
                "reportingApi" => $reportingApiMock
            ]
        );
        $piModel->cancel($paymentMock);
    }

    public function testCancelERROR()
    {
        $orderMock = $this->makeOrderMockWithStoreId();

        $reportingApiMock = $this->getMockBuilder(Reporting::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reportingApiMock
            ->expects($this->once())
            ->method("getTransactionDetails")->willReturn($this->makeReportingResult());

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $paymentMock->expects($this->once())
            ->method('getLastTransId')
            ->will($this->returnValue(self::TEST_VPSTXID));

        $piRestApiMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piRestApiMock
            ->expects($this->once())
            ->method('void')
            ->with(self::TEST_VPSTXID)
            ->willThrowException(new \Exception("Error in Voiding"));

        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "pirestapi"   => $piRestApiMock,
                "reportingApi" => $reportingApiMock
            ]
        );
        $response = "";
        try {
            $piModel->cancel($paymentMock);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = $e->getMessage();
        }

        $this->assertEquals(
            'Unable to VOID Sage Pay transaction ' . self::TEST_VPSTXID . ': Error in Voiding',
            $response
        );
    }

    public function testInitialize()
    {
        $orderMock = $this->makeOrderMockNoSendNewEmail();

        $paymentMock = $this->makePaymentMockForInitialize($orderMock);

        $stateMock = $this->makeStateObjectMock();
        $stateMock->expects($this->once())
            ->method('setStatus')
            ->with('pending_payment');
        $stateMock->expects($this->once())
            ->method('setState')
            ->with('pending_payment');
        $stateMock->expects($this->once())
            ->method('setIsNotified')
            ->with(false);

        $this->piModel->setInfoInstance($paymentMock);
        $this->piModel->initialize("Payment", $stateMock);
    }

    public function testInitializeDeferred()
    {
        $orderMock   = $this->makeOrderMockNoSendNewEmail();

        $paymentMock = $this->makePaymentMockForInitialize($orderMock);
        $paymentMock->expects($this->once())
            ->method('getLastTransId')
            ->willReturn('937F8F36-2BA5-2928-C0C9-7D1159895344');

        $stateMock = $this->makeStateObjectMock();
        $stateMock->expects($this->once())
            ->method('setState')
            ->with('new');
        $stateMock->expects($this->once())
            ->method('setStatus')
            ->with('pending');
        $stateMock->expects($this->once())
            ->method('setIsNotified')
            ->with(false);

        $this->piModel->setInfoInstance($paymentMock);
        $this->piModel->initialize('Deferred', $stateMock);
    }

    public function testGetConfigPaymentAction()
    {
        $this->configMock->expects($this->once())
            ->method('getPaymentAction');
        $this->piModel->getConfigPaymentAction();
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage This credit card type is not allowed for this payment method
     */
    public function testValidate()
    {
        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->once())
            ->method('getCountryId')
            ->willReturn("US");

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($addressMock);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->exactly(2))
            ->method('getCcType')
            ->will($this->returnValue("VI"));
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));

        $this->configMock
            ->expects($this->once())
            ->method('setMethodCode')
            ->willReturnSelf();
        $this->configMock
            ->expects($this->once())
            ->method('dropInEnabled')
            ->willReturn(false);
        $this->configMock->expects($this->once())
            ->method('getAllowedCcTypes')
            ->willReturn("MC,MI");
        $this->configMock->expects($this->never())->method('getAreSpecificCountriesAllowed');
        $this->configMock->expects($this->never())->method('getSpecificCountries');

        $this->piModel->setInfoInstance($paymentMock);

        $this->piModel->validate();
    }

    public function testAssignData()
    {
        $objMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objMock
            ->expects($this->exactly(4))
            ->method('getData')
            ->withConsecutive(
                ['additional_data'],
                ['cc_last4'],
                ['merchant_session_key'],
                ['card_identifier']
            )
            ->willReturnOnConsecutiveCalls([], '0006', 'some_key', 'card_id_string');

        $infoMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)->setMethods([
            'getInfoInstance',
            'encrypt',
            'decrypt',
            'setAdditionalInformation',
            'hasAdditionalInformation',
            'getAdditionalInformation',
            'getMethodInstance',
            'unsAdditionalInformation',
            'addData',
        ])->disableOriginalConstructor()->getMock();
        $infoMock->expects($this->exactly(3))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                ['cc_last4', '0006'],
                ['merchant_session_key', 'some_key'],
                ['card_identifier', 'card_id_string']
            );

        /** @var PI $piModelMock */
        $piModelMock = $this->getMockBuilder(PI::class)
            ->setMethods(['getInfoInstance'])
        ->disableOriginalConstructor()
        ->getMock();

        $piModelMock->expects($this->exactly(2))->method('getInfoInstance')->willReturn($infoMock);

        $return = $piModelMock->assignData($objMock);

        $this->assertInstanceOf('\Ebizmarts\SagePaySuite\Model\PI', $return);
    }

    public function testCanUseInternal()
    {
        $configMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->any())->method('setMethodCode')->with('sagepaysuitepi')->willReturnSelf();
        $configMock->expects($this->once())->method('isMethodActiveMoto')->willReturn(1);

        $form = $this->objectManagerHelper->getObject(
            '\Ebizmarts\SagePaySuite\Model\PI',
            [
                'config' => $configMock,
            ]
        );

        $this->assertTrue($form->canUseInternal());
    }

    public function testIsActive()
    {
        $scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeConfigMock->expects($this->any())->method('getValue')
            ->with('payment/sagepaysuitepi/active_moto')
            ->willReturn(1);

        $appStateMock = $this->getMockBuilder(\Magento\Framework\App\State::class)
            ->disableOriginalConstructor()->getMock();
        $appStateMock->expects($this->once())->method('getAreaCode')->willReturn('adminhtml');

        $contextMock = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())->method('getAppState')->willReturn($appStateMock);

        $form = $this->objectManagerHelper->getObject(
            '\Ebizmarts\SagePaySuite\Model\PI',
            [
                'context'     => $contextMock,
                'scopeConfig' => $scopeConfigMock
            ]
        );

        $this->assertTrue($form->isActive());
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage You can't use the payment type you selected to make payments to the billing country.
     */
    public function testValidateSpecificCountriesFailValidation()
    {
        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->once())
            ->method('getCountryId')
            ->willReturn("US");

        $quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($addressMock);

        $infoMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)->setMethods([
            'getInfoInstance',
            'encrypt',
            'decrypt',
            'setAdditionalInformation',
            'hasAdditionalInformation',
            'getAdditionalInformation',
            'getMethodInstance',
            'unsAdditionalInformation',
            'addData',
            'getQuote'
        ])->disableOriginalConstructor()->getMock();

        $infoMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $piModelMock = $this->getMockBuilder(PI::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['validate'])
            ->getMock();

        $piModelMock->expects($this->once())->method('getInfoInstance')->willReturn($infoMock);

        $piModelMock->validate();
    }

    public function testCapture()
    {
        $objectManager = new ObjectManager($this);

        $testAmount = 876.99;
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentOpsMock = $this->getMockBuilder(SagePayPayment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentOpsMock->expects($this->once())->method('capture')->with($paymentMock, $testAmount);

        /** @var PI $sut */
        $sut = $objectManager->getObject(
            PI::class,
            [
                'paymentOps' => $paymentOpsMock
            ]
        );

        $sut->capture($paymentMock, $testAmount);
    }

    public function testValidateOk()
    {
        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->once())
            ->method('getCountryId')
            ->willReturn("GB");

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($addressMock);

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->exactly(2))
            ->method('getCcType')
            ->will($this->returnValue("MI"));
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));

        $this->configMock
            ->expects($this->once())
            ->method('dropInEnabled')
            ->willReturn(false);
        $this->configMock
            ->expects($this->once())
            ->method('setMethodCode')
            ->willReturnSelf();
        $this->configMock->expects($this->once())
            ->method('getAllowedCcTypes')
            ->willReturn("MC,MI");
        $this->configMock->expects($this->never())->method('getAreSpecificCountriesAllowed');
        $this->configMock->expects($this->never())->method('getSpecificCountries');

        $this->piModel->setInfoInstance($paymentMock);

        $return = $this->piModel->validate();

        $this->assertInstanceOf('\Ebizmarts\SagePaySuite\Model\PI', $return);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makePaymentInfoMock()
    {
        $infoMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)->setMethods([
                    'getInfoInstance',
                    'encrypt',
                    'decrypt',
                    'setAdditionalInformation',
                    'hasAdditionalInformation',
                    'getAdditionalInformation',
                    'getMethodInstance',
                    'unsAdditionalInformation',
                    'addData',
                    'getQuote'
                ])->disableOriginalConstructor()->getMock();

        return $infoMock;
    }

    /**
     * @param $orderMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makePaymentMockForInitialize($orderMock)
    {
        $paymentMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')->disableOriginalConstructor()->getMock();
        $paymentMock->expects($this->once())->method('getOrder')->will($this->returnValue($orderMock));

        return $paymentMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeOrderMockNoSendNewEmail()
    {
        $orderMock = $this->getMockBuilder('Magento\Sales\Model\Order')->disableOriginalConstructor()->getMock();
        $orderMock->expects($this->once())->method('setCanSendNewEmailFlag')->with(false);

        return $orderMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeStateObjectMock()
    {
        $stateMock = $this->getMockBuilder('Magento\Framework\DataObject')->setMethods([
                "offsetExists",
                "offsetGet",
                "offsetSet",
                "offsetUnset",
                "setStatus",
                "setState",
                "setIsNotified"
            ])->disableOriginalConstructor()->getMock();

        return $stateMock;
    }

    /**
     * @return \stdClass
     */
    private function makeReportingResult()
    {
        $transactionDetails            = new \stdClass;
        $transactionDetails->txstateid = self::SUCCESSFULLY_AUTH_TRANSACTION;

        return $transactionDetails;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeOrderMockWithStoreId()
    {
        $orderMock = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $orderMock->expects($this->once())->method('getStoreId')->willReturn(1);

        return $orderMock;
    }
}
