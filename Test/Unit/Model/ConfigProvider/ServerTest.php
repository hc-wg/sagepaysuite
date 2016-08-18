<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\ConfigProvider;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\ConfigProvider\Server
     */
    protected $serverConfigProviderModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    protected function setUp()
    {
        $serverModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Server')
            ->disableOriginalConstructor()
            ->getMock();
        $serverModelMock->expects($this->any())
            ->method('isAvailable')
            ->willReturn(true);

        $paymentHelperMock = $this
            ->getMockBuilder('Magento\Payment\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentHelperMock->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($serverModelMock);

        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $customerSessionMock = $this
            ->getMockBuilder('Magento\Customer\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $customerSessionMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(1);

        $tokenModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Token')
            ->disableOriginalConstructor()
            ->getMock();
        $tokenModelMock->expects($this->any())
            ->method('getCustomerTokens')
            ->willReturn([
                "token_id" => 1
            ]);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->serverConfigProviderModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\ConfigProvider\Server',
            [
                "config" => $this->configMock,
                "paymentHelper" => $paymentHelperMock,
                'suiteHelper' => $suiteHelperMock,
                'customerSession' => $customerSessionMock,
                "tokenModel" => $tokenModelMock
            ]
        );
    }

    public function testGetConfig()
    {
        $this->configMock->expects($this->once())
            ->method('isTokenEnabled')
            ->willReturn(false);

        $this->assertEquals(
            [
                'payment' => [
                    'ebizmarts_sagepaysuiteserver' => [
                        'licensed' => null,
                        'token_enabled' => false,
                        'tokens' => null,
                        'max_tokens' => \Ebizmarts\SagePaySuite\Model\Config::MAX_TOKENS_PER_CUSTOMER,
                        'mode' => null
                    ]
                ]
            ],
            $this->serverConfigProviderModel->getConfig()
        );
    }

    public function testGetConfigWithToken()
    {
        $this->configMock->expects($this->once())
            ->method('isTokenEnabled')
            ->willReturn(true);

        $this->assertEquals(
            [
                'payment' => [
                    'ebizmarts_sagepaysuiteserver' => [
                        'licensed' => null,
                        'token_enabled' => true,
                        'tokens' => [
                            "token_id" => 1
                        ],
                        'max_tokens' => \Ebizmarts\SagePaySuite\Model\Config::MAX_TOKENS_PER_CUSTOMER,
                        'mode' => null
                    ]
                ]
            ],
            $this->serverConfigProviderModel->getConfig()
        );
    }
}
