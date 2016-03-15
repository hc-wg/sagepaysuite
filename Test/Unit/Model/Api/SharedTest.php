<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Api;

class SharedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared
     */
    protected $sharedApiModel;

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

        $reportingApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Reporting')
            ->disableOriginalConstructor()
            ->getMock();
        $reportingApiMock->expects($this->any())
            ->method('getTransactionDetails')
            ->will($this->returnValue((object)[
                "vpstxid" => 12345,
                "securitykey" => "fds87",
                "vpsauthcode" => "879243978234",
                "currency" => 'USD',
                "vendortxcode" => '1000000001-2016-12-12-12345678',

            ]));
        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock->expects($this->any())
            ->method('generateVendorTxCode')
            ->will($this->returnValue('1000000001-2016-12-12-12345'));

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
        $this->sharedApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Shared',
            [
                "reportingApi" => $reportingApiMock,
                "suiteHelper" => $suiteHelperMock,
                "curlFactory" => $curlFactoryMock,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock
            ]
        );
    }

    public function testVoidTransaction()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                'Status=OK'. PHP_EOL .
                'StatusDetail=OK STATUS'. PHP_EOL
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_VOID_TEST,
                '1.0',
                [],
                "VPSProtocol=&TxType=VOID&Vendor=&VendorTxCode=1000000001-2016-12-12-12345&VPSTxId=12345&SecurityKey=fds87&TxAuthNo=879243978234&"
            );

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    'Status' => 'OK',
                    'StatusDetail' => 'OK STATUS'
                ]
            ],
            $this->sharedApiModel->voidTransaction("12345")
        );
    }

    public function testRefundTransaction()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                'Status=OK'. PHP_EOL .
                'StatusDetail=OK STATUS'. PHP_EOL
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_REFUND_TEST,
                '1.0',
                [],
                "VPSProtocol=&TxType=REFUND&Vendor=&VendorTxCode=1000000001-2016-12-12-12345&Amount=100.00&Currency=USD&Description=Refund+issued+from+magento.&RelatedVPSTxId=12345&RelatedVendorTxCode=1000000001-2016-12-12-12345678&RelatedSecurityKey=fds87&RelatedTxAuthNo=879243978234&"
            );

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    'Status' => 'OK',
                    'StatusDetail' => 'OK STATUS'
                ]
            ],
            $this->sharedApiModel->refundTransaction("12345",100,1)
        );
    }

    public function testRefundTransactionERROR()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                'Status=INVALID'. PHP_EOL .
                'StatusDetail=2013 : INVALID STATUS'. PHP_EOL
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_REFUND_TEST,
                '1.0',
                [],
                "VPSProtocol=&TxType=REFUND&Vendor=&VendorTxCode=1000000001-2016-12-12-12345&Amount=100.00&Currency=USD&Description=Refund+issued+from+magento.&RelatedVPSTxId=12345&RelatedVendorTxCode=1000000001-2016-12-12-12345678&RelatedSecurityKey=fds87&RelatedTxAuthNo=879243978234&"
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("INVALID STATUS"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("INVALID STATUS")));

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        try {
            $this->sharedApiModel->refundTransaction("12345",100,1);
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException)
        {
            $this->assertEquals(
                "INVALID STATUS",
                $apiException->getUserMessage()
            );
        }
    }

    public function testReleaseTransaction()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                'Status=OK'. PHP_EOL .
                'StatusDetail=OK STATUS'. PHP_EOL
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_RELEASE_TEST,
                '1.0',
                [],
                "VPSProtocol=&TxType=RELEASE&Vendor=&VendorTxCode=1000000001-2016-12-12-12345678&VPSTxId=12345&SecurityKey=fds87&TxAuthNo=879243978234&ReleaseAmount=100.00&"
            );

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    'Status' => 'OK',
                    'StatusDetail' => 'OK STATUS'
                ]
            ],
            $this->sharedApiModel->releaseTransaction("12345",100)
        );
    }

    public function testAuthorizeTransaction()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                'Status=OK'. PHP_EOL .
                'StatusDetail=OK STATUS'. PHP_EOL
            );

        $this->curlMock->expects($this->once())
            ->method('getInfo')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_AUTHORIZE_TEST,
                '1.0',
                [],
                "VPSProtocol=&TxType=AUTHORISE&Vendor=&VendorTxCode=1000000001-2016-12-12-12345&Amount=100.00&Description=Authorize+transaction+from+Magento&RelatedVPSTxId=12345&RelatedVendorTxCode=1000000001-2016-12-12-12345678&RelatedSecurityKey=fds87&RelatedTxAuthNo=879243978234&"
            );

        $this->assertEquals(
            [
                "status" => 200,
                "data" => [
                    'Status' => 'OK',
                    'StatusDetail' => 'OK STATUS'
                ]
            ],
            $this->sharedApiModel->authorizeTransaction("12345",100,1)
        );
    }
}