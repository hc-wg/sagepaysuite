<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Plugin;

use Ebizmarts\SagePaySuite\Model\Api\Post;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Plugin\DeleteTokenFromSagePay;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class DeleteTokenFromSagePayTest extends TestCase
{
    public function testDeleteFromSagePay()
    {
        $token = '04C9FEF1-9746-4C5E-A2C0-731355ED80C8';
        $vendorName = 'testebizmarts';
        $VPSProtocol = '3.00';

        $configMock = $this
            ->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock
            ->expects($this->exactly(2))
            ->method('getVendorname')
            ->willReturn($vendorName);
        $configMock
            ->expects($this->once())
            ->method('getVPSProtocol')
            ->willReturn($VPSProtocol);

        $postApiMock = $this
            ->getMockBuilder(Post::class)
            ->disableOriginalConstructor()
            ->getMock();
        $postApiMock
            ->expects($this->once())
            ->method('sendPost')
            ->with(
                [
                    "VPSProtocol" => $VPSProtocol,
                    "TxType" => "REMOVETOKEN",
                    "Vendor" => 'testebizmarts',
                    "Token" => $token
                ],
                Config::URL_TOKEN_POST_REMOVE_TEST,
                ["OK"]
            );

        $objectManagerHelper = new ObjectManager($this);

        /** @var DeleteTokenFromSagePay $deleteTokenFromSagePay */
        $deleteTokenFromSagePay = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Plugin\DeleteTokenFromSagePay',
            [
                'config'  => $configMock,
                'postApi' => $postApiMock
            ]
        );

        $deleteTokenFromSagePay->deleteFromSagePay($token);
    }
}
