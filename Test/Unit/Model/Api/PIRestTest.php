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
        $this->configMock
            ->expects($this->once())
            ->method('getPIKey')
            ->willReturn(self::PI_KEY);
        $this->configMock
            ->expects($this->once())
            ->method('getPIPassword')
            ->willReturn(self::PI_PASSWORD);

        $this->httpRestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Api\HttpRest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->httpRestMock
            ->expects($this->once())
            ->method('setBasicAuth')
            ->with(self::PI_KEY, self::PI_PASSWORD);

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
    }

    /**
     * @dataProvider dataproviderCapture
     * @param $responseCode
     */
    public function testCapture($responseCode)
    {
        $threedResultMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD::class)
            ->disableOriginalConstructor()
            ->getMock();
        $threedResultMock->expects($this->once())->method('setStatus')->with("NotChecked");

        $threedResultFactoryMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeDFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $threedResultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($threedResultMock);

        $payResult = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethodResultFactory = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethodFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $paymentMethodResultFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($payResult);

        $cardResult = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCard::class)
            ->disableOriginalConstructor()
        ->getMock();
        $cardResult->expects($this->never())->method('setCardIdentifier');
        $cardResult->expects($this->never())->method('setIsReusable');
        $cardResult->expects($this->once())->method('setCardType')->with("Visa");
        $cardResult->expects($this->once())->method('setLastFourDigits')->with("0006");
        $cardResult->expects($this->once())->method('setExpiryDate')->with("0317");

        $cardResultFactory = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCardFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $cardResultFactory->expects($this->once())->method('create')->willReturn($cardResult);

        $payResult->expects($this->once())->method('setCard')->with($cardResult);

        $piTransactionResult = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piTransactionResult->expects($this->once())->method('setStatusCode')->with("0000");
        $piTransactionResult->expects($this->once())->method('setStatusDetail')->with("The Authorisation was Successful.");
        $piTransactionResult->expects($this->once())->method('setTransactionId')->with("T6569400-1516-0A3F-E3FA-7F222CC79221");
        $piTransactionResult->expects($this->once())->method('setStatus')->with("Ok");
        $piTransactionResult->expects($this->once())->method('setTransactionType')->with("Payment");
        $piTransactionResult->expects($this->once())->method('setRetrievalReference')->with("8636128");
        $piTransactionResult->expects($this->once())->method('setBankAuthCode')->with("999777");
        $piTransactionResult->expects($this->once())->method('setBankResponseCode')->with("00");
        $piTransactionResult->expects($this->once())->method('setPaymentMethod')->with($payResult);
        $piTransactionResult->expects($this->once())->method('setThreeDSecure')->with($threedResultMock);

        $piResultFactory = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $piResultFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($piTransactionResult);

        $this->httpRestMock
            ->expects($this->once())
            ->method('setUrl')
            ->with("https://test.sagepay.com/api/v1/transactions");

        $this->httpResponseMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(201);
        $this->httpResponseMock
            ->expects($this->once())
            ->method('getResponseData')
            ->willReturn(
                json_decode(
                    '
                        {
                            "transactionId": "T6569400-1516-0A3F-E3FA-7F222CC79221",
                            "transactionType": "Payment",
                            "status": "Ok",
                            "statusCode": "0000",
                            "statusDetail": "The Authorisation was Successful.",
                            "retrievalReference": 8636128,
                            "bankResponseCode": "00",
                            "bankAuthorisationCode": "999777",
                            "paymentMethod": {
                                "card": {
                                    "cardType": "Visa",
                                    "lastFourDigits": "0006",
                                    "expiryDate": "0317"
                                }
                            },
                            "3DSecure": {
                                "status": "NotChecked"
                            }
                        }
                    '
                )
            );

        $requestArray = [
            "transactionType" => "Payment",
            "paymentMethod" => [
                "card" => [
                    "merchantSessionKey" => "M1E996F5-A9BC-41FE-B088-E5B73DB94277",
                    "cardIdentifier" => "1234564766758",
                ]
            ],
            "vendorTxCode"      => "demotransaction-100092813",
            "amount"            => 10000,
            "currency"          => "GBP",
            "description"       => "Demotransaction",
            "apply3DSecure"     => "UseMSPSetting",
            "customerFirstName" => "Sam",
            "customerLastName"  => "Jones",
            "billingAddress" => [
                "address1"   => "407St.JohnStreet",
                "city"       => "London",
                "postalCode" => "EC1V4AB",
                "country"    => "GB",
            ],
            "entryMethod" => "Ecommerce"
        ];

        $this->httpRestMock
            ->expects($this->once())
            ->method('executePost')
            ->with('{"transactionType":"Payment","paymentMethod":{"card":{"merchantSessionKey":"M1E996F5-A9BC-41FE-B088-E5B73DB94277","cardIdentifier":"1234564766758"}},"vendorTxCode":"demotransaction-100092813","amount":10000,"currency":"GBP","description":"Demotransaction","apply3DSecure":"UseMSPSetting","customerFirstName":"Sam","customerLastName":"Jones","billingAddress":{"address1":"407St.JohnStreet","city":"London","postalCode":"EC1V4AB","country":"GB"},"entryMethod":"Ecommerce"}')
            ->willReturn($this->httpResponseMock);

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "httpRestFactory"            => $this->httpRestFactoryMock,
                "config"                     => $this->configMock,
                "apiExceptionFactory"        => $this->apiExceptionFactoryMock,
                "piCaptureResultFactory"     => $piResultFactory,
                "cardResultFactory"          => $cardResultFactory,
                "paymentMethodResultFactory" => $paymentMethodResultFactory,
                "threedResultFactory"        => $threedResultFactoryMock
            ]
        );

        $resultObject = $this->pirestApiModel->capture($requestArray);

        $this->assertInstanceOf('\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult', $resultObject);
    }

    public function dataproviderCapture()
    {
        return [
            [201], [202]
        ];
    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     * @expectedExceptionMessage Contains invalid value: paymentMethod.card.merchantSessionKey
     */
    public function testCaptureERROR()
    {
        $this->httpRestMock
            ->expects($this->once())
            ->method('executePost')
            ->with('{"Amount":"100.00"}')
            ->willReturn($this->httpResponseMock);

        $this->httpResponseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(422);
        $this->httpResponseMock
            ->expects($this->once())
            ->method('getResponseData')
            ->willReturn(
                json_decode(
                    '
                        {
                        "errors":
                            [
                                {"description":"Contains invalid value","property":"paymentMethod.card.merchantSessionKey","code":1009},
                                {"description":"Contains invalid value","property":"paymentMethod.card.cardIdentifier","code":1009}
                            ]
                        }
                    '
                )
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

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "httpRestFactory"            => $this->httpRestFactoryMock,
                "config"                     => $this->configMock,
                "apiExceptionFactory"        => $this->apiExceptionFactoryMock
            ]
        );

        $this->pirestApiModel->capture(["Amount" => "100.00"]);
    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     * @expectedExceptionMessage No card provided.
     */
    public function testCaptureError1()
    {
        $this->httpRestMock
            ->expects($this->once())
            ->method('executePost')
            ->with('{"Amount":"100.00"}')
            ->willReturn($this->httpResponseMock);

        $this->httpResponseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(422);
        $this->httpResponseMock
            ->expects($this->once())
            ->method('getResponseData')
            ->willReturn(
                json_decode(
                    '
                    {
                    "errors":
                    [
                      {"statusDetail": "No card provided.", "description":"Contains invalid value","property":"paymentMethod.card.cardIdentifier","code":1009}
                    ]
                    }
                    '
                )
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

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "httpRestFactory"            => $this->httpRestFactoryMock,
                "config"                     => $this->configMock,
                "apiExceptionFactory"        => $this->apiExceptionFactoryMock
            ]
        );

        $this->pirestApiModel->capture(["Amount" => "100.00"]);
    }

    public function testSubmit3D()
    {
        $pi3dRequestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiThreeDSecureRequest::class)
            ->setMethods(['__toArray', 'setParEs'])
            ->disableOriginalConstructor()
            ->getMock();
        $pi3dRequestMock->expects($this->once())->method('setParEs')->with("fsd678dfs786dfs786fds678fds");
        $pi3dRequestMock->expects($this->once())->method('__toArray')->willReturn(["paRes" => "fsd678dfs786dfs786fds678fds"]);

        $pi3dRequestFactoryMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiThreeDSecureRequestFactory::class)
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();
        $pi3dRequestFactoryMock->expects($this->once())->method('create')->willReturn($pi3dRequestMock);

        $piTransactionResult3DMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piTransactionResult3DMock->expects($this->once())->method('setStatus')->with("OK");

        $piTransactionResult3DFactoryMock = $this
        ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeDFactory::class)
        ->setMethods(["create"])
        ->disableOriginalConstructor()
        ->getMock();
        $piTransactionResult3DFactoryMock->expects($this->once())->method('create')->willReturn($piTransactionResult3DMock);

        $this->httpRestMock
            ->expects($this->once())
            ->method('executePost')
            ->with('{"paRes":"fsd678dfs786dfs786fds678fds"}')
            ->willReturn($this->httpResponseMock);

        $this->httpResponseMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(201);
        $this->httpResponseMock
            ->expects($this->once())
            ->method('getResponseData')
            ->willReturn(json_decode('{"status": "OK"}'));

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "httpRestFactory"            => $this->httpRestFactoryMock,
                "config"                     => $this->configMock,
                "threedRequest"              => $pi3dRequestFactoryMock,
                "threedStatusResultFactory"  => $piTransactionResult3DFactoryMock
            ]
        );

        $this->assertInstanceOf(
            '\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD',
            $this->pirestApiModel->submit3D("fsd678dfs786dfs786fds678fds", 12345)
        );
    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     * @expectedExceptionMessage Contains invalid characters: paRes
     */
    public function testSubmitThreedError()
    {
        $pi3dRequestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiThreeDSecureRequest::class)
            ->setMethods(['__toArray', 'setParEs'])
            ->disableOriginalConstructor()
            ->getMock();
        $pi3dRequestMock->expects($this->once())->method('setParEs')->with("fsd678dfs786dfs786fds678fds");
        $pi3dRequestMock->expects($this->once())->method('__toArray')->willReturn(["paRes" => "fsd678dfs786dfs786fds678fds"]);

        $pi3dRequestFactoryMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiThreeDSecureRequestFactory::class)
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();
        $pi3dRequestFactoryMock->expects($this->once())->method('create')->willReturn($pi3dRequestMock);

        $piTransactionResult3DFactoryMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeDFactory::class)
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();
        $piTransactionResult3DFactoryMock->expects($this->never())->method('create');

        $this->httpRestMock
            ->expects($this->once())
            ->method('executePost')
            ->with('{"paRes":"fsd678dfs786dfs786fds678fds"}')
            ->willReturn($this->httpResponseMock);

        $this->httpResponseMock
            ->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(422);
        $this->httpResponseMock
            ->expects($this->once())
            ->method('getResponseData')
            ->willReturn(json_decode('{"errors": [{"description": "Contains invalid characters","property": "paRes","code": 1005}]}'));

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

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "httpRestFactory"            => $this->httpRestFactoryMock,
                "config"                     => $this->configMock,
                "apiExceptionFactory"        => $this->apiExceptionFactoryMock,
                "threedRequest"              => $pi3dRequestFactoryMock,
                "threedStatusResultFactory"  => $piTransactionResult3DFactoryMock
            ]
        );
        $this->pirestApiModel->submit3D("fsd678dfs786dfs786fds678fds", 12345);
    }

//    public function testTransactionDetails()
//    {
//        $threedResultMock = $this
//            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD::class)
//            ->disableOriginalConstructor()
//            ->getMock();
//        $threedResultMock->expects($this->once())->method('setStatus')->with("NotChecked");
//
//        $threedResultFactoryMock = $this
//            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeDFactory::class)
//            ->disableOriginalConstructor()
//            ->setMethods(['create'])
//            ->getMock();
//        $threedResultFactoryMock->expects($this->once())
//            ->method('create')
//            ->willReturn($threedResultMock);
//        $cardResult = $this
//            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCard::class)
//            ->disableOriginalConstructor()
//            ->getMock();
//        $cardResult->expects($this->never())->method('setCardIdentifier');
//        $cardResult->expects($this->never())->method('setIsReusable');
//        $cardResult->expects($this->once())->method('setCardType')->with("Visa");
//        $cardResult->expects($this->once())->method('setLastFourDigits')->with("0006");
//        $cardResult->expects($this->once())->method('setExpiryDate')->with("0317");
//
//        $cardResultFactory = $this
//            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCardFactory')
//            ->disableOriginalConstructor()
//            ->setMethods(['create'])
//            ->getMock();
//        $cardResultFactory->expects($this->once())->method('create')->willReturn($cardResult);
//        $payResult = $this
//            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethod::class)
//            ->disableOriginalConstructor()
//            ->getMock();
//        $paymentMethodResultFactory = $this
//            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethodFactory')
//            ->disableOriginalConstructor()
//            ->setMethods(['create'])
//            ->getMock();
//        $paymentMethodResultFactory
//            ->expects($this->once())
//            ->method('create')
//            ->willReturn($payResult);
//        $piTransactionResult = $this
//            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult::class)
//            ->disableOriginalConstructor()
//            ->getMock();
//        $piTransactionResult->expects($this->once())->method('setStatusCode')->with("0000");
//        $piTransactionResult->expects($this->once())->method('setStatusDetail')->with("The Authorisation was Successful.");
//        $piTransactionResult->expects($this->once())->method('setTransactionId')->with("T6569400-1516-0A3F-E3FA-7F222CC79221");
//        $piTransactionResult->expects($this->once())->method('setStatus')->with("Ok");
//        $piTransactionResult->expects($this->once())->method('setTransactionType')->with("Payment");
//        $piTransactionResult->expects($this->once())->method('setRetrievalReference')->with("8636128");
//        $piTransactionResult->expects($this->once())->method('setBankAuthCode')->with("999777");
//        $piTransactionResult->expects($this->once())->method('setBankResponseCode')->with("00");
//        $piTransactionResult->expects($this->once())->method('setPaymentMethod')->with($payResult);
//        $piTransactionResult->expects($this->once())->method('setThreeDSecure')->with($threedResultMock);
//
//        $piTransactionResultFactory = $this
//            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory::class)
//            ->setMethods(['create'])
//            ->disableOriginalConstructor()
//            ->getMock();
//        $piTransactionResultFactory->expects($this->once())->method('create')->willReturn($piTransactionResult);
//
//        $this->httpRestMock
//            ->expects($this->once())
//            ->method('executeGet')
//            ->willReturn($this->httpResponseMock);
//
//        $this->httpResponseMock
//            ->expects($this->once())
//            ->method('getStatus')
//            ->willReturn(200);
//        $this->httpResponseMock
//            ->expects($this->once())
//            ->method('getResponseData')
//            ->willReturn(json_decode(
//                    '
//                        {
//                            "transactionId": "T6569400-1516-0A3F-E3FA-7F222CC79221",
//                            "transactionType": "Payment",
//                            "status": "Ok",
//                            "statusCode": "0000",
//                            "statusDetail": "The Authorisation was Successful.",
//                            "retrievalReference": 8636128,
//                            "bankResponseCode": "00",
//                            "bankAuthorisationCode": "999777",
//                            "paymentMethod": {
//                                "card": {
//                                    "cardType": "Visa",
//                                    "lastFourDigits": "0006",
//                                    "expiryDate": "0317"
//                                }
//                            },
//                            "3DSecure": {
//                                "status": "NotChecked"
//                            }
//                        }
//                    '
//            ));
//
//        $this->pirestApiModel  = $this->objectManager->getObject(
//            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
//            [
//                "httpRestFactory"            => $this->httpRestFactoryMock,
//                "config"                     => $this->configMock,
//                "apiExceptionFactory"        => $this->apiExceptionFactoryMock,
//                "piCaptureResultFactory"     => $piTransactionResultFactory
//            ]
//        );
//
//        $this->assertEquals(
//            (object)[
//                "VPSTxId" => "12345"
//            ],
//            $this->pirestApiModel->transactionDetails(12345)
//        );
//    }

    /**
     * @expectedException \Ebizmarts\SagePaySuite\Model\Api\ApiException
     * @expectedExceptionMessage Invalid Transaction Id
     */
    public function testTransactionDetailsERROR()
    {
        $this->httpRestMock
            ->expects($this->once())
            ->method('executeGet')
            ->willReturn($this->httpResponseMock);

        $this->httpResponseMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(400);
        $this->httpResponseMock
            ->expects($this->exactly(2))
            ->method('getResponseData')
            ->willReturn(json_decode('{"description": "Contains invalid characters","property": "paRes","code": 1005}'));

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("Invalid Transaction Id"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("Invalid Transaction Id"))
        );
        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($apiException);

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "httpRestFactory"            => $this->httpRestFactoryMock,
                "config"                     => $this->configMock,
                "apiExceptionFactory"        => $this->apiExceptionFactoryMock,
            ]
        );

        $this->pirestApiModel->transactionDetails(12345);
    }

    public function testVoidSucess()
    {
        $piInstructionRequest = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiInstructionRequest::class)
        ->disableOriginalConstructor()
            ->setMethods(['setInstructionType', '__toArray'])
            ->getMock();
        $piInstructionRequest->expects($this->once())->method('setInstructionType')->with("void");
        $piInstructionRequest->expects($this->once())->method('__toArray')->willReturn(["instructionType" => "void"]);
        $piInstructionRequestFactory = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiInstructionRequestFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $piInstructionRequestFactory->expects($this->once())->method('create')->willReturn($piInstructionRequest);

        $instructionResponse = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiInstructionResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['__toArray'])
            ->getMock();

        $instructionResponseFactory = $this
        ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiInstructionResponseFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $instructionResponseFactory->expects($this->once())->method('create')->willReturn($instructionResponse);

        $this->httpRestMock
            ->expects($this->once())
            ->method('executePost')
            ->with('{"instructionType":"void"}')
            ->willReturn($this->httpResponseMock);

        $this->httpResponseMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(201);
        $this->httpResponseMock
            ->expects($this->once())
            ->method('getResponseData')
            ->willReturn(json_decode('{"instructionType": "void","date": "2015-08-11T11:45:16.285+01:00"}'));

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "httpRestFactory"            => $this->httpRestFactoryMock,
                "config"                     => $this->configMock,
                "apiExceptionFactory"        => $this->apiExceptionFactoryMock,
                "instructionRequest"         => $piInstructionRequestFactory,
                "instructionResponse"        => $instructionResponseFactory,
            ]
        );
        $result = $this->pirestApiModel->void("2B97808F-9A36-6E71-F87F-6714667E8AF4");
        $this->assertEquals($result->getInstructionType(), "void");
        $this->assertEquals($result->getDate(), "2015-08-11T11:45:16.285+01:00");
    }

    public function testRefundSucess()
    {
        $refundRequestMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiRefundRequest::class)
            ->setMethods(['__toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $refundRequestMock
            ->expects($this->once())
            ->method('__toArray')
            ->willReturn(['vendorName' => 'testvendorname']);
        $refundRequestFactoryMock = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiRefundRequestFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $refundRequestFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($refundRequestMock);
        $this->httpRestMock
            ->expects($this->once())
            ->method('setUrl');

        $this->httpResponseMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(201);
        $this->httpResponseMock
            ->expects($this->once())
            ->method('getResponseData')
            ->willReturn(
                json_decode(
                    '
                    {
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
                    }
                    '
                )
            );

        $this->httpRestMock
            ->expects($this->once())
            ->method('executePost')
            ->with('{"vendorName":"testvendorname"}')
            ->willReturn($this->httpResponseMock);

        $piTransactionResult = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piTransactionResult->expects($this->once())->method('setStatusCode')->with("0000");
        $piTransactionResult->expects($this->once())->method('setStatusDetail')->with("The Authorisation was Successful.");
        $piTransactionResult->expects($this->once())->method('setTransactionId')->with("043D6711-E722-ACC6-2C2E-B03E00BF7603");
        $piTransactionResult->expects($this->once())->method('setStatus')->with("Ok");
        $piTransactionResult->expects($this->once())->method('setTransactionType')->with("Refund");
        $piTransactionResult->expects($this->once())->method('setRetrievalReference')->with("13551640");
        $piTransactionResult->expects($this->once())->method('setBankAuthCode')->with("999778");
        $piTransactionResult->expects($this->never())->method('setBankResponseCode');
        //$piTransactionResult->expects($this->once())->method('setPaymentMethod')->with($payResult);
        //$piTransactionResult->expects($this->once())->method('setThreeDSecure')->with($threedResultMock);

        $cardResult = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cardResult->expects($this->never())->method('setCardIdentifier');
        $cardResult->expects($this->never())->method('setIsReusable');
        $cardResult->expects($this->once())->method('setCardType')->with("MasterCard");
        $cardResult->expects($this->once())->method('setLastFourDigits')->with("0001");
        $cardResult->expects($this->once())->method('setExpiryDate')->with("0520");

        $cardResultFactory = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCardFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $cardResultFactory->expects($this->once())->method('create')->willReturn($cardResult);

        $piResultFactory = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $piResultFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($piTransactionResult);

        $payResult = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethodResultFactory = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethodFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $paymentMethodResultFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($payResult);

        $this->pirestApiModel  = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "config"                     => $this->configMock,
                "refundRequest"              => $refundRequestFactoryMock,
                "piCaptureResultFactory"     => $piResultFactory,
                "httpRestFactory"            => $this->httpRestFactoryMock,
                "cardResultFactory"          => $cardResultFactory,
                "paymentMethodResultFactory" => $paymentMethodResultFactory
            ]
        );

        $this->pirestApiModel->refund(
            "R000000122-2016-12-22-1423481482416628",
            "2B97808F-9A36-6E71-F87F-6714667E8AF4",
            10800,
            "GBP",
            "Magento backend refund."
        );
    }
}
