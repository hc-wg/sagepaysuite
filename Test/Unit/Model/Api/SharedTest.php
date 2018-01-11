<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Api;

class SharedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared
     */
    private $sharedApiModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apiExceptionFactoryMock;

    /** @var  \Ebizmarts\SagePaySuite\Model\Api\HttpText|PHPUnit_Framework_MockObject_MockObject */
    private $httpTextMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->apiExceptionFactoryMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();

        $transactionDetails = new \stdClass();
        $transactionDetails->vpstxid      = "12345";
        $transactionDetails->securitykey  = "fds87";
        $transactionDetails->vpsauthcode  = "879243978234";
        $transactionDetails->currency     = "USD";
        $transactionDetails->vendortxcode = "1000000001-2016-12-12-12345678";

        $reportingApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Reporting')
            ->disableOriginalConstructor()
            ->getMock();
        $reportingApiMock->expects($this->any())
            ->method('getTransactionDetails')
            ->willReturn($transactionDetails);
        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock->expects($this->any())
            ->method('generateVendorTxCode')
            ->will($this->returnValue('1000000001-2016-12-12-12345'));

        $suiteRequestHelperMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['populateAddressInformation'])
            ->getMock();

        $storerMock = $this
            ->getMockBuilder('Magento\Store\Model\Store')
            ->disableOriginalConstructor()
            ->getMock();
        $storerMock->expects($this->any())
            ->method("getBaseCurrencyCode")
            ->willReturn("USD");
        $storerMock->expects($this->any())
            ->method("getDefaultCurrencyCode")
            ->willReturn("EUR");
        $storerMock->expects($this->any())
            ->method("getCurrentCurrencyCode")
            ->willReturn("GBP");

        $storeManagerMock = $this
            ->getMockBuilder('Magento\Store\Model\StoreManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $storeManagerMock->expects($this->any())
            ->method("getStore")
            ->willReturn($storerMock);

        $loggerMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scopeConfigMock = $this
            ->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->setMethods(['getMode','getVendorname'])
            ->setConstructorArgs(
                ['scopeConfig' => $scopeConfigMock, 'storeManager' => $storeManagerMock, 'logger' => $loggerMock]
            )
            ->getMock();
        $configMock->method('getMode')->willReturn('test');
        $configMock->method('getVendorname')->willReturn('testvendorname');

        $this->httpTextMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\HttpText::class)
            ->setMethods(['executePost', 'getResponseData', 'arrayToQueryParams'])
            ->disableOriginalConstructor()
            ->getMock();

        $httpTextFactoryMock = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Model\Api\HttpTextFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $httpTextFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->httpTextMock);


        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->sharedApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Shared',
            [
                "reportingApi"        => $reportingApiMock,
                "suiteHelper"         => $suiteHelperMock,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock,
                "config"              => $configMock,
                'suiteRequestHelper'  => $suiteRequestHelperMock,
                "httpTextFactory"     => $httpTextFactoryMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testVoidTransaction()
    {
        $stringResponse = 'HTTP/1.1 200 OK';
        $stringResponse .= "\n\n";
        $stringResponse .= "VPSProtocol=3.00\n";
        $stringResponse .= "Status=OK\n";
        $stringResponse .= "StatusDetail=Success.\n";

        $responseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\HttpResponse::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $responseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(200);

        $this->httpTextMock
            ->method('getResponseData')
            ->willReturn($stringResponse);
        $this->httpTextMock
            ->expects($this->once())
            ->method('arrayToQueryParams')
            ->with(
                [
                    'VPSProtocol'  => '3.00',
                    'TxType'       => 'VOID',
                    'Vendor'       => "testvendorname",
                    'VendorTxCode' => "1000000001-2016-12-12-12345",
                    'SecurityKey'  => "fds87",
                    'TxAuthNo'     => "879243978234",
                    "VPSTxId"      => "12345"
                ]
            );
        $this->httpTextMock
            ->expects($this->once())
            ->method('executePost')
            ->willReturn($responseMock);

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    'VPSProtocol'  => '3.00',
                    'Status'       => 'OK',
                    'StatusDetail' => 'Success.'
                ]
            ],
            $this->sharedApiModel->voidTransaction("12345")
        );
    }

    public function testRefundTransaction()
    {
        $stringResponse = 'HTTP/1.1 200 OK';
        $stringResponse .= "\n\n";
        $stringResponse .= "VPSProtocol=3.00\n";
        $stringResponse .= "Status=OK\n";
        $stringResponse .= "StatusDetail=Success.\n";
        $stringResponse .= "VPSTxId=123456\n";
        $stringResponse .= "TxAuthNo=8792439782345\n";

        $responseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\HttpResponse::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $responseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(200);

        $this->httpTextMock
            ->method('getResponseData')
            ->willReturn($stringResponse);
        $this->httpTextMock
            ->expects($this->once())
            ->method('arrayToQueryParams')
            ->with(
                [
                    'VPSProtocol'         => '3.00',
                    'TxType'              => 'REFUND',
                    'Vendor'              => "testvendorname",
                    'VendorTxCode'        => "1000000001-2016-12-12-12345",
                    'Amount'              => "100.00",
                    'Currency'            => "USD",
                    'Description'         => "Refund issued from magento.",
                    'RelatedVPSTxId'      => "12345",
                    'RelatedVendorTxCode' => "1000000001-2016-12-12-12345678",
                    "RelatedSecurityKey"  => "fds87",
                    "RelatedTxAuthNo"     => "879243978234"
                ]
            );
        $this->httpTextMock
            ->expects($this->once())
            ->method('executePost')
            ->willReturn($responseMock);

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    'VPSProtocol'  => '3.00',
                    'VPSTxId'      => '123456',
                    'TxAuthNo'     => '8792439782345',
                    'Status'       => 'OK',
                    'StatusDetail' => 'Success.'
                ]
            ],
            $this->sharedApiModel->refundTransaction("12345", 100, 1)
        );
    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     * @expectedExceptionMessage The Transaction has already been Refunded.
     */
    public function testRefundTransactionERROR()
    {
        $stringResponse = 'HTTP/1.1 200 OK';
        $stringResponse .= "\n\n";
        $stringResponse .= "VPSProtocol=3.00\n";
        $stringResponse .= "Status=INVALID\n";
        $stringResponse .= "StatusDetail=INVALID : The Transaction has already been Refunded.\n";
        $stringResponse .= "VPSTxId=123456\n";
        $stringResponse .= "TxAuthNo=8792439782345\n";

        $responseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\HttpResponse::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $responseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(200);

        $this->httpTextMock
            ->method('getResponseData')
            ->willReturn($stringResponse);
        $this->httpTextMock
            ->expects($this->once())
            ->method('arrayToQueryParams')
            ->with(
                [
                    'VPSProtocol'         => '3.00',
                    'TxType'              => 'REFUND',
                    'Vendor'              => "testvendorname",
                    'VendorTxCode'        => "1000000001-2016-12-12-12345",
                    'Amount'              => "100.00",
                    'Currency'            => "USD",
                    'Description'         => "Refund issued from magento.",
                    'RelatedVPSTxId'      => "12345",
                    'RelatedVendorTxCode' => "1000000001-2016-12-12-12345678",
                    "RelatedSecurityKey"  => "fds87",
                    "RelatedTxAuthNo"     => "879243978234"
                ]
            );
        $this->httpTextMock
            ->expects($this->once())
            ->method('executePost')
            ->willReturn($responseMock);

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("The Transaction has already been Refunded."),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("INVALID"))
        );
        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        $this->sharedApiModel->refundTransaction("12345", 100, 1);
    }

    public function testReleaseTransaction()
    {
        $stringResponse = 'HTTP/1.1 200 OK';
        $stringResponse .= "\n\n";
        $stringResponse .= "VPSProtocol=3.00\n";
        $stringResponse .= "Status=OK\n";
        $stringResponse .= "StatusDetail=Success.\n";

        $responseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\HttpResponse::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $responseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(200);

        $this->httpTextMock
            ->method('getResponseData')
            ->willReturn($stringResponse);
        $this->httpTextMock
            ->expects($this->once())
            ->method('arrayToQueryParams')
            ->with(
                [
                    'VPSProtocol'   => '3.00',
                    'TxType'        => 'RELEASE',
                    'Vendor'        => "testvendorname",
                    'VendorTxCode'  => "1000000001-2016-12-12-12345678",
                    'VPSTxId'       => "12345",
                    "SecurityKey"   => "fds87",
                    "TxAuthNo"      => "879243978234",
                    'ReleaseAmount' => "100.00",
                ]
            );
        $this->httpTextMock
            ->expects($this->once())
            ->method('executePost')
            ->willReturn($responseMock);

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    'Status'       => 'OK',
                    'StatusDetail' => 'Success.',
                    'VPSProtocol'  => '3.00'
                ]
            ],
            $this->sharedApiModel->releaseTransaction("12345", 100)
        );
    }

    public function testAuthorizeTransaction()
    {
        $stringResponse = 'HTTP/1.1 200 OK';
        $stringResponse .= "\n\n";
        $stringResponse .= "VPSProtocol=3.00\n";
        $stringResponse .= "Status=OK\n";
        $stringResponse .= "StatusDetail=Success.\n";
        $stringResponse .= "VPSTxId=123456\n";
        $stringResponse .= "TxAuthNo=2439782345\n";
        $stringResponse .= "SecurityKey=8759623519\n";
        $stringResponse .= "BankAuthCode=T99777\n";
        $stringResponse .= "DeclineCode=00\n";

        $responseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\HttpResponse::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $responseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(200);

        $this->httpTextMock
            ->method('getResponseData')
            ->willReturn($stringResponse);
        $this->httpTextMock
            ->expects($this->once())
            ->method('arrayToQueryParams')
            ->with(
                [
                    'VPSProtocol'         => '3.00',
                    'TxType'              => 'AUTHORISE',
                    'Vendor'              => "testvendorname",
                    'VendorTxCode'        => "1000000001-2016-12-12-12345",
                    'Amount'              => "100.00",
                    'Description'         => "Authorise transaction from Magento",
                    'RelatedVPSTxId'      => "12345",
                    'RelatedVendorTxCode' => "1000000001-2016-12-12-12345678",
                    "RelatedSecurityKey"  => "fds87",
                    "RelatedTxAuthNo"     => "879243978234"
                ]
            );
        $this->httpTextMock
            ->expects($this->once())
            ->method('executePost')
            ->willReturn($responseMock);

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    'Status'       => 'OK',
                    'StatusDetail' => 'Success.',
                    'VPSTxId'      => '123456',
                    'VPSProtocol'  => '3.00',
                    'TxAuthNo'     => '2439782345',
                    'SecurityKey'  => '8759623519',
                    'BankAuthCode' => 'T99777',
                    'DeclineCode'  => '00'
                ]
            ],
            $this->sharedApiModel->authorizeTransaction("12345", 100, 1)
        );
    }

    public function testRepeatTransaction()
    {
        $stringResponse = 'HTTP/1.1 200 OK';
        $stringResponse .= "\n\n";
        $stringResponse .= "VPSProtocol=3.00\n";
        $stringResponse .= "Status=OK\n";
        $stringResponse .= "StatusDetail=Success.\n";
        $stringResponse .= "VPSTxId=123456\n";
        $stringResponse .= "TxAuthNo=2439782345\n";
        $stringResponse .= "SecurityKey=8759623519\n";
        $stringResponse .= "BankAuthCode=T99777\n";
        $stringResponse .= "DeclineCode=00\n";

        $responseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\HttpResponse::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $responseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(200);

        $this->httpTextMock
            ->method('getResponseData')
            ->willReturn($stringResponse);
        $this->httpTextMock
            ->expects($this->once())
            ->method('arrayToQueryParams')
            ->with(
                [
                    'VPSProtocol'   => '3.00',
                    'TxType'        => 'REPEAT',
                    'Vendor'        => "testvendorname",
                    //'VendorTxCode'  => "1000000001-2016-12-12-123456",
                    //'Amount'  => "100",
                    //'Currency'  => "USD",
                    'Description'  => "Repeat transaction from Magento",
                    'RelatedVPSTxId'       => "12345",
                    "RelatedVendorTxCode"   => "1000000001-2016-12-12-12345678",
                    "RelatedSecurityKey"      => "fds87",
                    'RelatedTxAuthNo' => "879243978234",
                ]
            );
        $this->httpTextMock
            ->expects($this->once())
            ->method('executePost')
            ->willReturn($responseMock);

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    'Status'       => 'OK',
                    'StatusDetail' => 'Success.',
                    'VPSTxId'      => '123456',
                    'VPSProtocol'  => '3.00',
                    'TxAuthNo'     => '2439782345',
                    'SecurityKey'  => '8759623519',
                    'BankAuthCode' => 'T99777',
                    'DeclineCode'  => '00'
                ]
            ],
            $this->sharedApiModel->repeatTransaction("12345", [])
        );
    }
}
