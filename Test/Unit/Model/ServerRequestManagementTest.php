<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class ServerRequestManagementTest extends \PHPUnit_Framework_TestCase
{

    public function testSavePaymentInformationAndPlaceOrderNoToken()
    {
        $configMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->setMethods(
                [
                    'getBasketFormat',
                    'getSagepayPaymentAction',
                    'getVendorname',
                    'get3Dsecure',
                    'getAvsCvc',
                    'isGiftAidEnabled',
                    'getPaypalBillingAgreement',
                    'isServerLowProfileEnabled',
                    'getMode'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->once())->method('getBasketFormat')->willReturn("Disabled");
        $configMock->expects($this->exactly(2))->method('getSagepayPaymentAction')->willReturn("PAYMENT");
        $configMock->expects($this->exactly(2))->method('getMode')->willReturn("test");
        $configMock->expects($this->exactly(3))->method('getVendorname')->willReturn("testebizmarts");
        $configMock->expects($this->once())->method('get3Dsecure')->willReturn(0);
        $configMock->expects($this->once())->method('getAvsCvc')->willReturn(0);
        $configMock->expects($this->once())->method('isGiftAidEnabled')->willReturn(0);
        $configMock->expects($this->once())->method('getPaypalBillingAgreement')->willReturn(0);
        $configMock->expects($this->once())->method('isServerLowProfileEnabled')->willReturn(0);

        $helperMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $postApiMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\Post::class)
            ->disableOriginalConstructor()
            ->getMock();
        $postApiMock->method('sendPost')->willReturn(
            [
                'data' => [
                    'VPSTxId'     => "F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F",
                    "SecurityKey" => "AAABARR5kw"
                ]
            ]
        );

        $suiteLoggerMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->setMethods(['setTransactionId', 'setLastTransId', 'setAdditionalInformation', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())->method('setTransactionId')->with("F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F");
        $paymentMock->expects($this->once())->method('setLastTransId')->with("F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F");
        $paymentMock->expects($this->exactly(5))->method('setAdditionalInformation');
        $paymentMock->expects($this->once())->method('save')->willReturnSelf();
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);

        $checkoutHelperMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Checkout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutHelperMock->expects($this->once())->method('placeOrder')->willReturn($orderMock);

        $requestHelperMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Request::class)
            ->setMethods(['populatePaymentAmount', 'populateAddressInformation'])
            ->disableOriginalConstructor()
            ->getMock();
        $requestHelperMock->expects($this->once())->method('populatePaymentAmount')->willReturn(
            [
                'Amount' => 56.98,
                'Currency' => 'GBP',
            ]
        );
        $requestHelperMock->expects($this->once())->method('populateAddressInformation')->willReturn(
            [
                'CustomerEMail' => 'testcustomer@ebizmarts.com',
                'BillingSurname' => 'Surname',
                'BillingFirstnames' => 'BFirst Names',
                'BillingAddress1' => 'Alfa 1234',
                'BillingAddress2' => '',
                'BillingCity' => 'London',
                'BillingPostCode' => 'ABC 1234',
                'BillingCountry' => 'GB',
                'BillingPhone' => '0707089865857',
                'Deliveryurname' => 'Surname',
                'DeliveryFirstnames' => 'BFirst Names',
                'DeliveryAddress1' => 'Alfa 1234',
                'DeliveryAddress2' => '',
                'DeliveryCity' => 'London',
                'DeliveryPostCode' => 'ABC 1234',
                'DeliveryCountry' => 'GB',
                'DeliveryPhone' => '87415487'
            ]
        );

        $tokenModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Token::class)
            ->setMethods(['isCustomerUsingMaxTokenSlots'])
            ->disableOriginalConstructor()
            ->getMock();
        $tokenModelMock->expects($this->once())->method('isCustomerUsingMaxTokenSlots')->willReturn(false);

        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)->getMock();
        $customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerSessionMock->expects($this->once())->method('getCustomerDataObject')->willReturn($customerMock);

        $quoteRepositoryMock = $this->getMockBuilder(\Magento\Quote\Api\CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreUrl = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteIdMaskFactory = $this->getMockBuilder(\Magento\Quote\Model\QuoteIdMaskFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())->method('collectTotals')->willReturnSelf();
        $quoteMock->expects($this->once())->method('reserveOrderId')->willReturnSelf();
        $quoteMock->expects($this->once())->method('getReservedOrderId')->willReturn(123);
        $quoteMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);

        $checkoutSessionMock->method('getQuote')->willReturn($quoteMock);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $resultObject        = $objectManagerHelper->getObject('\Ebizmarts\SagePaySuite\Api\Data\FormResult');

        /** @var \Ebizmarts\SagePaySuite\Model\ServerRequestManagement $requestManager */
        $requestManager = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\ServerRequestManagement::class)
            ->setMethods(['getQuoteById'])
            ->setConstructorArgs(
                [
                    'config'             => $configMock,
                    'suiteHelper'        => $helperMock,
                    'postApi'            => $postApiMock,
                    'suiteLogger'        => $suiteLoggerMock,
                    'checkoutHelper'     => $checkoutHelperMock,
                    'requestHelper'      => $requestHelperMock,
                    'tokenModel'         => $tokenModelMock,
                    'checkoutSession'    => $checkoutSessionMock,
                    'customerSession'    => $customerSessionMock,
                    'result'             => $resultObject,
                    'quoteRepository'    => $quoteRepositoryMock,
                    'coreUrl'            => $coreUrl,
                    'quoteIdMaskFactory' => $quoteIdMaskFactory,
                ]
            )
            ->getMock();
        $requestManager->expects($this->once())->method('getQuoteById')->willReturn($quoteMock);

        $response = $requestManager->savePaymentInformationAndPlaceOrder(456, false, '%token');

        $this->assertTrue($response->getSuccess());
        $this->assertArrayHasKey('data', $response->getResponse());
        $this->assertArrayHasKey('VPSTxId', $response->getResponse()['data']);
        $this->assertArrayHasKey('SecurityKey', $response->getResponse()['data']);
    }

}