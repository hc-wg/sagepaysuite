<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class FormRequestManagementTest extends \PHPUnit_Framework_TestCase
{
    public function testResponseIsOk()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $configMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->setMethods(
                [
                    'getFormEncryptedPassword',
                    'getBasketFormat',
                    'getMode',
                    'getSagepayPaymentAction',
                    'getVendorname',
                    'getFormVendorEmail',
                    'getFormSendEmail',
                    'getFormEmailMessage',
                    'get3Dsecure',
                    'getAvsCvc',
                    'isGiftAidEnabled'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->once())->method('getFormEncryptedPassword')->willReturn("0303456nanana");
        $configMock->expects($this->once())->method('getBasketFormat')->willReturn("Disabled");
        $configMock->expects($this->once())->method('getMode')->willReturn("test");
        $configMock->expects($this->once())->method('getSagepayPaymentAction')->willReturn("PAYMENT");
        $configMock->expects($this->once())->method('getVendorname')->willReturn("testebizmarts");
        $configMock->expects($this->once())->method('getFormVendorEmail')->willReturn("testvendor@ebizmarts.com");
        $configMock->expects($this->once())->method('getFormSendEmail')->willReturn(0);
        $configMock->expects($this->once())->method('getFormEmailMessage')->willReturn("");
        $configMock->expects($this->once())->method('get3Dsecure')->willReturn(0);
        $configMock->expects($this->once())->method('getAvsCvc')->willReturn(0);
        $configMock->expects($this->once())->method('isGiftAidEnabled')->willReturn(0);

        $helperMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteLoggerMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestHelperMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Request::class)
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

        $resultObject = $objectManagerHelper->getObject('\Ebizmarts\SagePaySuite\Api\Data\Result');

        $checkoutSessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerSessionMock = $this
            ->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteRepoMock = $this
            ->getMockBuilder(\Magento\Quote\Api\CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteIdMaskRepoMock = $this
            ->getMockBuilder(\Magento\Quote\Model\QuoteIdMaskFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $url = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())->method('collectTotals')->willReturnSelf();
        $quoteMock->expects($this->once())->method('reserveOrderId')->willReturnSelf();
        $quoteMock->expects($this->once())->method('save')->willReturnSelf();

        $objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManager\ObjectManager::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $phpseclib = new \phpseclib\Crypt\AES(\phpseclib\Crypt\Base::MODE_CBC);
        $objectManagerMock
            ->method('create')
            ->willReturn($phpseclib);

        $requestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\FormRequestManagement::class)
            ->setMethods(['getQuoteById'])
            ->setConstructorArgs(
                [
                    "config"             => $configMock,
                    "suiteHelper"        => $helperMock,
                    "suiteLogger"        => $suiteLoggerMock,
                    "requestHelper"      => $requestHelperMock,
                    "result"             => $resultObject,
                    "checkoutSession"    => $checkoutSessionMock,
                    "customerSession"    => $customerSessionMock,
                    "quoteRepository"    => $quoteRepoMock,
                    "quoteIdMaskFactory" => $quoteIdMaskRepoMock,
                    "coreUrl"            => $url,
                    "objectManager"      => $objectManagerMock
                ]
            )
            ->getMock();

        $requestMock->expects($this->once())->method('getQuoteById')->willReturn($quoteMock);

        /** @var \Ebizmarts\SagePaySuite\Api\Data\ResultInterface $response */
        $response = $requestMock->getEncryptedRequest(456);

        $this->assertTrue($response->getSuccess());
        $this->isJson($response->getResponse());

        $jsonResp = json_decode($response->getResponse());
        $this->assertEquals('https://test.sagepay.com/gateway/service/vspform-register.vsp', $jsonResp->redirect_url);
        $this->assertEquals('3.00', $jsonResp->vps_protocol);
        $this->assertEquals('PAYMENT', $jsonResp->tx_type);
        $this->assertEquals('testebizmarts', $jsonResp->vendor);
        $this->assertStringStartsWith('@', $jsonResp->crypt);
    }

}