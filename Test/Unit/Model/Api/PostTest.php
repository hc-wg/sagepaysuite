<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Api;

class PostTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post
     */
    protected $postApiModel;

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
        $this->postApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Post',
            [
                "curlFactory" => $curlFactoryMock,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock
            ]
        );
    }

    public function testSendPost()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                'Status=OK'. PHP_EOL .
                'StatusDetail=OK STATUS'. PHP_EOL .
                'URL2=http://example2.com?test=1&test2=2'. PHP_EOL
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_LIVE,
                '1.0',
                [],
                'Amount=100.00&Vendorname=testebizmarts&URL=http%3A%2F%2Fexample.com%3Ftest%3D1%26test2%3D2&'
            );

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    "Status" => "OK",
                    "StatusDetail" => "OK STATUS",
                    "URL2" => "http://example2.com?test=1&test2=2"
                ]
            ],
            $this->postApiModel->sendPost(
                [
                    "Amount" => "100.00",
                    "Vendorname" => "testebizmarts",
                    "URL" => "http://example.com?test=1&test2=2"
                ],
                \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_LIVE,
                ["OK"]
            )
        );
    }

    public function testSendPostERROR()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                'Status=INVALID'. PHP_EOL .
                'StatusDetail=INVALID ERROR'. PHP_EOL
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_LIVE,
                '1.0',
                [],
                'Amount=100.00&Vendorname=testebizmarts&URL=http%3A%2F%2Fexample.com%3Ftest%3D1%26test2%3D2&'
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("INVALID ERROR"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("INVALID ERROR"))
        );

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        try {
            $this->postApiModel->sendPost(
                [
                    "Amount" => "100.00",
                    "Vendorname" => "testebizmarts",
                    "URL" => "http://example.com?test=1&test2=2"
                ],
                \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_LIVE,
                ["OK"]
            );
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->assertEquals(
                "INVALID ERROR",
                $apiException->getUserMessage()
            );
        }
    }
}
