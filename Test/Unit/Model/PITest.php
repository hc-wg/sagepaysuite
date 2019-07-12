<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class PITest extends \PHPUnit_Framework_TestCase
{
    private $objectManagerHelper;
    private $suiteHelperMock;
    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';
    /**
     * @var \Ebizmarts\SagePaySuite\Model\PI
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
    public function testRefund()
    {
        $this->configMock
            ->expects($this->once())
            ->method('getCurrencyConfig')
            ->willReturn(\Ebizmarts\SagePaySuite\Model\Config::CURRENCY_BASE);
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn("1000001");

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));
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
                'Magento backend refund.'
            )
            ->willReturn($returnMock);
        $transactionAmountFactoryMock = $this->getMockBuilder(
            'Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmountFactory'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $transactionAmountFactoryMock
            ->expects($this->never())
            ->method('create');
        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config"      => $this->configMock,
                "suiteHelper" => $this->suiteHelperMock,
                "pirestapi"   => $piRestApiMock,
                "transactionAmountFactory" => $transactionAmountFactoryMock
            ]
        );
        $piModel->refund($paymentMock, 100);
    }
    public function testRefundCurrencySwitcher()
    {
        $this->configMock
            ->expects($this->once())
            ->method('getCurrencyConfig')
            ->willReturn(\Ebizmarts\SagePaySuite\Model\Config::CURRENCY_SWITCHER);
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn("1000001");
        $orderMock->expects($this->once())
            ->method('getOrderCurrencyCode')
            ->willReturn('EUR');
        $orderMock->expects($this->once())
            ->method('getBaseCurrencyCode')
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
                'Magento backend refund.'
            )
            ->willReturn($returnMock);
        $transactionAmountMock = $this->getMockBuilder(
            \Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmount::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $transactionAmountMock->expects($this->once())->method('getCommand')->with('EUR')
            ->willReturn(
                new \Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmountDefaultPi(100)
            );
        $transactionAmountFactoryMock = $this
            ->getMockBuilder(
                'Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmountFactory'
            )
            ->disableOriginalConstructor()
            ->getMock();
        $transactionAmountFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($transactionAmountMock);
        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config"      => $this->configMock,
                "suiteHelper" => $this->suiteHelperMock,
                "pirestapi"   => $piRestApiMock,
                "transactionAmountFactory" => $transactionAmountFactoryMock
            ]
        );
        $piModel->refund($paymentMock, 100);
    }
    public function testRefundERROR()
    {
        $this->configMock
            ->expects($this->once())
            ->method('getCurrencyConfig')
            ->willReturn(\Ebizmarts\SagePaySuite\Model\Config::CURRENCY_BASE);
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn("1000001");
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));
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
                'Magento backend refund.'
            )
            ->willThrowException(new \Exception("Error in Refunding"));
        $transactionAmountFactoryMock = $this->getMockBuilder(
            'Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmountFactory'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $transactionAmountFactoryMock
            ->expects($this->never())
            ->method('create');
        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config"      => $this->configMock,
                "suiteHelper" => $this->suiteHelperMock,
                "pirestapi"   => $piRestApiMock,
                "transactionAmountFactory" => $transactionAmountFactoryMock
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
        $this->configMock
            ->expects($this->once())
            ->method('getCurrencyConfig')
            ->willReturn(\Ebizmarts\SagePaySuite\Model\Config::CURRENCY_BASE);
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn("1000001");
        $this->suiteHelperMock
            ->expects($this->once())
            ->method('generateVendorTxCode')
            ->willReturn('R1000001');
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));
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
                'Magento backend refund.'
            )
            ->willThrowException(
                new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
                    new \Magento\Framework\Phrase("The Transaction has already been Refunded.")
                )
            );
        $transactionAmountFactoryMock = $this->getMockBuilder(
            'Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmountFactory'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $transactionAmountFactoryMock
            ->expects($this->never())
            ->method('create');
        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "config"      => $this->configMock,
                "suiteHelper" => $this->suiteHelperMock,
                "pirestapi"   => $piRestApiMock,
                "transactionAmountFactory" => $transactionAmountFactoryMock
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
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
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
        /** @var \Ebizmarts\SagePaySuite\Model\PI $piModel */
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
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
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
        /** @var \Ebizmarts\SagePaySuite\Model\PI $piModel */
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
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
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
        /** @var \Ebizmarts\SagePaySuite\Model\PI $piModel */
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
        $paymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
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
            ->with(self::TEST_VPSTXID);
        $piModel = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                "pirestapi"   => $piRestApiMock
            ]
        );
        $piModel->cancel($paymentMock);
    }
    public function testCancelERROR()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
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
                "pirestapi"   => $piRestApiMock
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
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('setCanSendNewEmailFlag')
            ->with(false);
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));
        $stateMock = $this
            ->getMockBuilder('Magento\Framework\DataObject')
            ->setMethods(["offsetExists", "offsetGet", "offsetSet", "offsetUnset", "setStatus", "setIsNotified"])
            ->disableOriginalConstructor()
            ->getMock();
        $stateMock->expects($this->once())
            ->method('setStatus')
            ->with('pending_payment');
        $stateMock->expects($this->once())
            ->method('setIsNotified')
            ->with(false);
        $this->piModel->setInfoInstance($paymentMock);
        $this->piModel->initialize("", $stateMock);
    }
    public function testGetConfigPaymentAction()
    {
        $this->configMock->expects($this->once())
            ->method('getPaymentAction');
        $this->piModel->getConfigPaymentAction();
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
        /** @var \Ebizmarts\SagePaySuite\Model\PI $piModelMock */
        $piModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PI::class)
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
        $piModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PI::class)
            ->disableOriginalConstructor()
            ->setMethods(['getInfoInstance', 'getConfigData', 'assignData', 'canUseForCountry'])
            ->getMock();
        $piModelMock->expects($this->any())->method('getInfoInstance')->willReturn($infoMock);
        $piModelMock->validate();
    }
    public function testValidateOk()
    {
        $scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['payment/sagepaysuitepi/allowspecific', 'store'],
                ['payment/sagepaysuitepi/specificcountry', 'store']
            )
            ->willReturnOnConsecutiveCalls('1', 'US');
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
        $paymentMock->expects($this->never())
            ->method('getCcType');
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
        /** @var \Ebizmarts\SagePaySuite\Model\PI $sut */
        $sut = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                'scopeConfig' => $scopeConfigMock
            ]
        );
        $sut->setInfoInstance($paymentMock);
        $return = $sut->validate();
        $this->assertInstanceOf(\Ebizmarts\SagePaySuite\Model\PI::class, $return);
    }
    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage You can't use the payment type you selected to make payments to the billing country.
     */
    public function testValidateNotEnabledForUnitedStates()
    {
        $scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['payment/sagepaysuitepi/allowspecific', 'store'],
                ['payment/sagepaysuitepi/specificcountry', 'store']
            )
            ->willReturnOnConsecutiveCalls('1', 'UK');
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
        $paymentMock->expects($this->never())
            ->method('getCcType');
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
        /** @var \Ebizmarts\SagePaySuite\Model\PI $sut */
        $sut = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\PI',
            [
                'scopeConfig' => $scopeConfigMock
            ]
        );
        $sut->setInfoInstance($paymentMock);
        $sut->validate();
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
}
