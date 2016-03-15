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
    protected $pirestApiModel;

    /**
     * @var \Magento\Framework\HTTP\Adapter\Curl|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $curlMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apiExceptionFactoryMock;

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
        $curlFactoryMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\Adapter\CurlFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();
        $curlFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->curlMock));

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->pirestApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\PIRest',
            [
                "curlFactory" => $curlFactoryMock,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock
            ]
        );
    }

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
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_GENERATE_MERCHANT_KEY,
                '1.0',
                array('Content-type: application/json'),
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
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_GENERATE_MERCHANT_KEY,
                '1.0',
                array('Content-type: application/json'),
                '{"vendorName":null}'
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("error description"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("error description")));

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        try {
            $this->pirestApiModel->generateMerchantKey();
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException)
        {
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
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_CAPTURE_TRANSACTION,
                '1.0',
                array('Content-type: application/json'),
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

    public function testCaptureERROR()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"code": "2001, "description": "Invalid address", "property": "Invalid post code"}'
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(401);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_CAPTURE_TRANSACTION,
                '1.0',
                array('Content-type: application/json'),
                '{"Amount":"100.00"}'
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("Invalid address: Invalid post code"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("Invalid address: Invalid post code")));

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        try {
            $this->pirestApiModel->capture(
                [
                    "Amount" => "100.00"
                ]
            );
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException)
        {
            $this->assertEquals(
                "Invalid address: Invalid post code",
                $apiException->getUserMessage()
            );
        }
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
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . "transactions/" . 12345 . "/" . \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_SUBMIT_3D,
                '1.0',
                array('Content-type: application/json'),
                '{"paRes":"fsd678dfs786dfs786fds678fds"}'
            );

        $this->assertEquals(
            (object)[
                "status" => "OK"
            ],
            $this->pirestApiModel->submit3D("fsd678dfs786dfs786fds678fds",12345)
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
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . "transactions/" . 12345 . "/" . \Ebizmarts\SagePaySuite\Model\Api\PIRest::ACTION_SUBMIT_3D,
                '1.0',
                array('Content-type: application/json'),
                '{"paRes":"fsd678dfs786dfs786fds678fds"}'
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("Invalid PaRES"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("Invalid PaRES")));

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        try {
            $this->pirestApiModel->submit3D("fsd678dfs786dfs786fds678fds",12345);
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException)
        {
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
                array('Content-type: application/json')
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
                \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . "transactions/" . 12345,
                '1.0',
                array('Content-type: application/json')
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("Invalid Transaction Id"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("Invalid Transaction Id")));

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        try {
            $this->pirestApiModel->transactionDetails(12345);
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException)
        {
            $this->assertEquals(
                "Invalid Transaction Id",
                $apiException->getUserMessage()
            );
        }
    }
}