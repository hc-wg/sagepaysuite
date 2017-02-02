<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Api;

class PostTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post
     */
    private $postApiModel;

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

        $suiteHelperMock = $this
        ->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Request::class)
            ->setMethods(['populateAddressInformation'])
        ->disableOriginalConstructor()
        ->getMock();

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
        $this->postApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Post',
            [
                "apiExceptionFactory" => $this->apiExceptionFactoryMock,
                'suiteHelper'         => $suiteHelperMock,
                "httpTextFactory"     => $httpTextFactoryMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testSendPost()
    {
        $stringResponse = 'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
            'Status=OK'. PHP_EOL .
            'StatusDetail=OK STATUS'. PHP_EOL .
            'URL2=http://example2.com?test=1&test2=2'. PHP_EOL;

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
                    'URL'        => "http://example.com?test=1&test2=2",
                    'Amount'     => '100.00',
                    'Vendorname' => 'testebizmarts',
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
                    "URL2"         => "http://example2.com?test=1&test2=2",
                    "Status"       => "OK",
                    "StatusDetail" => "OK STATUS",
                ]
            ],
            $this->postApiModel->sendPost(
                [
                    "Amount"     => "100.00",
                    "URL"        => "http://example.com?test=1&test2=2",
                    "Vendorname" => "testebizmarts"
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
