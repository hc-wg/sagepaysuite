<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Api;

class PIRestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRest
     */
    private $pirestApiModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apiExceptionFactoryMock;

    private $objectManager;

    /** @var \Ebizmarts\SagePaySuite\Model\Api\HttpRest|\PHPUnit_Framework_MockObject_MockObject */
    private $httpRestMock;

    private $httpRestFactoryMock;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\HttpResponse|\PHPUnit_Framework_MockObject_MockObject */
    private $httpResponseMock;

    /** @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    const PI_KEY      = "hJYxsw7HLbj40cB8udES8CDRFLhuJ8G54O6rDpUXvE6hYDrria";
    const PI_PASSWORD = "o2iHSrFybYMZpmWOQMuhsXP52V4fBtpuSDshrKDSWsBY1OiN6hwd9Kb12z4j5Us5u";

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->apiExceptionFactoryMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpRestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\HttpRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->httpRestFactoryMock = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Model\Api\HttpRestFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->httpRestFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->httpRestMock);

        $this->httpResponseMock = $this->
            getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\HttpResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

    }
    // @codingStandardsIgnoreEnd

    public function testGenerateMerchantKey()
    {
        $this->configMock
            ->expects($this->once())
            ->method('getPIKey')
            ->willReturn(self::PI_KEY);
        $this->configMock
            ->expects($this->once())
            ->method('getPIPassword')
            ->willReturn(self::PI_PASSWORD);

        $mskRequestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyRequest::class)
            ->setMethods(['__toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mskRequestMock
            ->expects($this->once())
            ->method('__toArray')
            ->willReturn(['vendorName' => 'testvendorname']);
        $mskRequestMockFactory = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyRequestFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $mskRequestMockFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($mskRequestMock);
        $this->httpRestMock
            ->expects($this->once())
            ->method('setBasicAuth')
            ->with(self::PI_KEY, self::PI_PASSWORD);
        $this->httpRestMock
            ->expects($this->once())
            ->method('setUrl')
            ->with("https://test.sagepay.com/api/v1/merchant-session-keys");

        $this->httpResponseMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(201);
        $this->httpResponseMock
            ->expects($this->once())
            ->method('getResponseData')
            ->willReturn(
                json_decode(
                    '{"merchantSessionKey":"M1E996F5-A9BC-41FE-B088-E5B73DB94277","expiry":"2025-08-11T11:45:16.285+01:00"}'
                )
            );

        $this->httpRestMock
            ->expects($this->once())
            ->method('executePost')
            ->with('{"vendorName":"testvendorname"}')
            ->willReturn($this->httpResponseMock);

        $mskResponseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mskResponseFactory = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyResponseFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $mskResponseFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($mskResponseMock);
        $mskResponseMock
            ->expects($this->once())
            ->method('setExpiry')
            ->with("2025-08-11T11:45:16.285+01:00");
        $mskResponseMock
            ->expects($this->once())
            ->method('setMerchantSessionKey')
            ->with("M1E996F5-A9BC-41FE-B088-E5B73DB94277");

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "mskRequest"      => $mskRequestMockFactory,
                "httpRestFactory" => $this->httpRestFactoryMock,
                "mskResponse"     => $mskResponseFactory,
                "config"          => $this->configMock
            ]
        );

        $this->assertInstanceOf(
            '\Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyResponse',
            $this->pirestApiModel->generateMerchantKey()
        );
    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     * @expectedExceptionMessage Missing mandatory field: vendorName
     */
    public function testGenerateMerchantKeyERROR()
    {
        $this->configMock
            ->expects($this->once())
            ->method('getPIKey')
            ->willReturn(self::PI_KEY);
        $this->configMock
            ->expects($this->once())
            ->method('getPIPassword')
            ->willReturn(self::PI_PASSWORD);

        $mskRequestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyRequest::class)
            ->setMethods(['__toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mskRequestMock
            ->expects($this->once())
            ->method('__toArray')
            ->willReturn(['vendorName' => '']);
        $mskRequestMockFactory = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyRequestFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $mskRequestMockFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($mskRequestMock);
        $this->httpRestMock
            ->expects($this->once())
            ->method('setBasicAuth')
            ->with(self::PI_KEY, self::PI_PASSWORD);
        $this->httpRestMock
            ->expects($this->once())
            ->method('setUrl')
            ->with("https://test.sagepay.com/api/v1/merchant-session-keys");

        $this->httpResponseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(422);
        $this->httpResponseMock
            ->expects($this->once())
            ->method('getResponseData')
            ->willReturn(
                json_decode(
                    '{"errors": [{"description": "Missing mandatory field","property": "vendorName","code": 1003}]}'
                )
            );

        $this->httpRestMock
            ->expects($this->once())
            ->method('executePost')
            ->with('{"vendorName":""}')
            ->willReturn($this->httpResponseMock);

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("Missing mandatory field: vendorName"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("Missing mandatory field: vendorName"))
        );

        $phrase = new \Magento\Framework\Phrase("Missing mandatory field: vendorName");

        $this->apiExceptionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with(["phrase" => $phrase, "code" => "1003"])
            ->willReturn($apiException);

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "mskRequest"          => $mskRequestMockFactory,
                "httpRestFactory"     => $this->httpRestFactoryMock,
                "config"              => $this->configMock,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock
            ]
        );

        $this->pirestApiModel->generateMerchantKey();

//        $this->curlMock->expects($this->once())
//            ->method('read')
//            ->willReturn(
//                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
//                '{"errors": [{"description": "Missing mandatory field","property": "vendorName","code": 1003}]}'
//            );
//
//        $this->curlMock->expects($this->once())
//            ->method('getInfo')
//            ->willReturn(422);
//
//        $this->curlMock->expects($this->once())
//            ->method('write')
//            ->with(
//                \Zend_Http_Client::POST,
//                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST .
//                \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_GENERATE_MERCHANT_KEY,
//                '1.0',
//                ['Content-type: application/json'],
//                '{"vendorName":null}'
//            );
//
//        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
//            new \Magento\Framework\Phrase("error description"),
//            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("error description"))
//        );
//
//        $this->apiExceptionFactoryMock->expects($this->any())
//            ->method('create')
//            ->will($this->returnValue($apiException));
//
//        $this->pirestApiModel->generateMerchantKey();
    }

    /**
     * @dataProvider dataproviderCapture
     */
    public function testCapture($responseCode)
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"status": "OK"}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn($responseCode);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST .
                \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_TRANSACTIONS,
                '1.0',
                ['Content-type: application/json'],
                '{"Amount":"100.00"}'
            );

        $this->assertEquals(
            (object)[
                "status" => "OK"
            ],
            $this->pirestApiModel->capture(
                [
                    "Amount" => "100.00"
                ]
            )
        );
    }

    public function dataproviderCapture()
    {
        return [
            [201], [202]
        ];
    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     */
    public function testCaptureERROR()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"errors":[{"description":"Contains invalid value","property":"paymentMethod.card.merchantSessionKey","code":1009},{"description":"Contains invalid value","property":"paymentMethod.card.cardIdentifier","code":1009}]}'
            );
        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(422);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST .
                \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_TRANSACTIONS,
                '1.0',
                ['Content-type: application/json'],
                '{"Amount":"100.00"}'
            );

        $apiExceptionObj = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("Contains invalid value: paymentMethod.card.merchantSessionKey"),
            new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase("Contains invalid value: paymentMethod.card.merchantSessionKey")
            )
        );

        $this->apiExceptionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with([
                'phrase' => __("Contains invalid value: paymentMethod.card.merchantSessionKey"),
                'code' => 1009
            ])
            ->willReturn($apiExceptionObj);

        $this->pirestApiModel->capture(["Amount" => "100.00"]);
    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     */
    public function testCaptureError1()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"errors":[{"statusDetail": "No card provided.", "description":"Contains invalid value","property":"paymentMethod.card.cardIdentifier","code":1009}]}'
            );
        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(422);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST .
                \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_TRANSACTIONS,
                '1.0',
                ['Content-type: application/json'],
                '{"Amount":"100.00"}'
            );

        $apiExceptionObj = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("No card provided."),
            new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase("No card provided.")
            )
        );

        $this->apiExceptionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with([
                'phrase' => __("No card provided."),
                'code' => 1009
            ])
            ->willReturn($apiExceptionObj);

        $this->pirestApiModel->capture(["Amount" => "100.00"]);
    }

    public function testSubmit3D()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"status": "OK"}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(201);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST .
                "transactions/" . 12345 . "/" . \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_SUBMIT_3D,
                '1.0',
                ['Content-type: application/json'],
                '{"paRes":"fsd678dfs786dfs786fds678fds"}'
            );

        $this->assertEquals(
            (object)[
                "status" => "OK"
            ],
            $this->pirestApiModel->submit3D("fsd678dfs786dfs786fds678fds", 12345)
        );
    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     */
    public function testSubmitThreedError()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"errors": [{"description": "Contains invalid characters","property": "paRes","code": 1005}]}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(422);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . "transactions/" . 12345 . "/" .
                \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_SUBMIT_3D,
                '1.0',
                ['Content-type: application/json'],
                '{"paRes":"fsd678dfs786dfs786fds678fds"}'
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("Contains invalid characters: paRes"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("Contains invalid characters: paRes"))
        );

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->with([
                'phrase' => __("Contains invalid characters: paRes"),
                'code'   => 1005
            ])
            ->willReturn($apiException);

        $this->pirestApiModel->submit3D("fsd678dfs786dfs786fds678fds", 12345);
    }

    public function testTransactionDetails()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"VPSTxId": "12345"}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::GET,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . "transactions/" . 12345,
                '1.0',
                ['Content-type: application/json']
            );

        $this->assertEquals(
            (object)[
                "VPSTxId" => "12345"
            ],
            $this->pirestApiModel->transactionDetails(12345)
        );
    }

    public function testTransactionDetailsERROR()
    {
        $configMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->once())->method('getMode')->willReturn('live');

        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"code": "2001","description": "Invalid Transaction Id"}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(400);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::GET,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_LIVE . "transactions/" . 12345,
                '1.0',
                ['Content-type: application/json']
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("Invalid Transaction Id"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("Invalid Transaction Id"))
        );

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->pirestApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "curlFactory"         => $this->curlFactoryMock,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock,
                "config"              => $configMock
            ]
        );

        try {
            $this->pirestApiModel->transactionDetails(12345);
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->assertEquals(
                "Invalid Transaction Id",
                $apiException->getUserMessage()
            );
        }
    }

    public function testVoidSucess()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"instructionType": "void","date": "2015-08-11T11:45:16.285+01:00"}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(201);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST .
                "transactions/2B97808F-9A36-6E71-F87F-6714667E8AF4/instructions",
                '1.0',
                ['Content-type: application/json'],
                '{"instructionType":"void"}'
            );

        $result = $this->pirestApiModel->void("2B97808F-9A36-6E71-F87F-6714667E8AF4");
        $this->assertEquals($result->instructionType, "void");
        $this->assertEquals($result->date, "2015-08-11T11:45:16.285+01:00");
    }

    public function testRefundSucess()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{
                    "statusCode": "0000",
                    "statusDetail": "The Authorisation was Successful.",
                    "transactionId": "043D6711-E722-ACC6-2C2E-B03E00BF7603",
                    "transactionType": "Refund",
                    "retrievalReference": 13551640,
                    "bankAuthorisationCode": "999778",
                    "paymentMethod": {
                        "card": {
                            "cardType": "MasterCard",
                            "lastFourDigits": "0001",
                            "expiryDate": "0520"
                        }
                    },
                    "status": "Ok"
                }'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(201);

        $write = preg_replace('/\s\s+/', '', str_replace(array("\r","\n"), '', '{
                    "transactionType":"Refund",
                    "vendorTxCode":"R000000122-2016-12-22-1423481482416628",
                    "referenceTransactionId":"2B97808F-9A36-6E71-F87F-6714667E8AF4",
                    "amount":10800,
                    "currency":"GBP",
                    "description":"Magento backend refund."
                 }'));

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST .
                \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_TRANSACTIONS,
                '1.0',
                ['Content-type: application/json'],
                $write
            );

        $result = $this->pirestApiModel->refund(
            "R000000122-2016-12-22-1423481482416628",
            "2B97808F-9A36-6E71-F87F-6714667E8AF4",
            10800,
            "GBP",
            "Magento backend refund."
        );

        $this->assertEquals($result->transactionType, "Refund");
        $this->assertEquals($result->transactionId, "043D6711-E722-ACC6-2C2E-B03E00BF7603");
        $this->assertEquals($result->statusCode, "0000");
        $this->assertEquals($result->statusDetail, "The Authorisation was Successful.");
        $this->assertEquals($result->retrievalReference, "13551640");
        $this->assertEquals($result->bankAuthorisationCode, "999778");
        $this->assertEquals($result->status, "Ok");
        $this->assertObjectHasAttribute("paymentMethod", $result);
    }
}
