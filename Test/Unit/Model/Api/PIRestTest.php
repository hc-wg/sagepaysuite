<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Api;

class PIRestTest extends \PHPUnit_Framework_TestCase
{
    private $curlFactoryMock;
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRest
     */
    private $pirestApiModel;

    /**
     * @var \Magento\Framework\HTTP\Adapter\Curl|\PHPUnit_Framework_MockObject_MockObject
     */
    private $curlMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apiExceptionFactoryMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->apiExceptionFactoryMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->curlMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\Adapter\Curl')
            ->disableOriginalConstructor()
            ->getMock();
        $this->curlFactoryMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\Adapter\CurlFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->curlFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->curlMock));

        $objectManagerHelper   = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->pirestApiModel  = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "curlFactory"         => $this->curlFactoryMock,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testGenerateMerchantKey()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"merchantSessionKey": "fds678f6d7s86f78ds68f7dsfd"}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(201);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST .
                \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_GENERATE_MERCHANT_KEY,
                '1.0',
                ['Content-type: application/json'],
                '{"vendorName":null}'
            );

        $this->assertEquals(
            'fds678f6d7s86f78ds68f7dsfd',
            $this->pirestApiModel->generateMerchantKey()
        );
    }

    public function testGenerateMerchantKeyERROR()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"code": "2012","description": "error description"}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(401);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST .
                \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_GENERATE_MERCHANT_KEY,
                '1.0',
                ['Content-type: application/json'],
                '{"vendorName":null}'
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("error description"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("error description"))
        );

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        try {
            $this->pirestApiModel->generateMerchantKey();
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->assertEquals(
                "error description",
                $apiException->getUserMessage()
            );
        }
    }

    public function testCapture()
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

    public function testSubmit3DERROR()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"code": "2001","description": "Invalid PaRES"}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(401);

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
            new \Magento\Framework\Phrase("Invalid PaRES"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("Invalid PaRES"))
        );

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        try {
            $this->pirestApiModel->submit3D("fsd678dfs786dfs786fds678fds", 12345);
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->assertEquals(
                "Invalid PaRES",
                $apiException->getUserMessage()
            );
        }
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
