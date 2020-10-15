<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\ConfigProvider;

class PITest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\ConfigProvider\PI
     */
    private $piConfigProviderModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    public function testGetConfig()
    {
        $piModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\PI')
            ->disableOriginalConstructor()
            ->getMock();
        $piModelMock->expects($this->any())
            ->method('isAvailable')
            ->willReturn(true);

        $paymentHelperMock = $this
            ->getMockBuilder('Magento\Payment\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentHelperMock->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($piModelMock);

        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock
            ->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock
            ->expects($this->once())
            ->method('getMode')
            ->willReturn('test');

        $this->configMock
            ->expects($this->once())
            ->method('dropInEnabled')
            ->willReturn(true);

        $this->configMock
            ->expects($this->once())
            ->method('shouldUse3dV2')
            ->willReturn(true);

        $this->configMock
            ->expects($this->exactly(2))
            ->method('setMethodCode')
            ->with('sagepaysuitepi')
            ->willReturnSelf();

        $this->configMock
            ->expects($this->once())
            ->method('get3dNewWindow')
            ->willReturn(true);

        $this->configMock
            ->expects($this->once())
            ->method('getMaxTokenPerCustomer')
            ->willReturn(2);

        $this->configMock
            ->expects($this->once())
            ->method('isTokenEnabled')
            ->willReturn(true);

        $customerId = 1;
        $customerSessionMock = $this
            ->getMockBuilder('Magento\Customer\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $customerSessionMock
            ->expects($this->exactly(2))
            ->method('getCustomerId')
            ->willReturn($customerId);

        $tokensToShowOnGrid = [
            [
                'id' => 1,
                'customer_id' => $customerId,
                'cc_last_4' => '5559',
                'cc_type' => 'VI',
                'cc_exp_month' => '12',
                'cc_exp_year' => '23'
            ]
        ];
        $vaultDetailsHandlerMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $vaultDetailsHandlerMock
            ->expects($this->once())
            ->method('getTokensFromCustomerToShowOnGrid')
            ->with($customerId)
            ->willReturn($tokensToShowOnGrid);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->piConfigProviderModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\ConfigProvider\PI',
            [
                "config"               => $this->configMock,
                "paymentHelper"        => $paymentHelperMock,
                "suiteHelper"          => $suiteHelperMock,
                "storeManager"         => $this->getStoreManagerMock(),
                "_customerSession"     => $customerSessionMock,
                "_vaultDetailsHandler" => $vaultDetailsHandlerMock
            ]
        );

        $this->assertEquals(
            [
                'payment' => [
                    'ebizmarts_sagepaysuitepi' => [
                        'licensed'     => true,
                        'mode'         => 'test',
                        'dropin'       => true,
                        'sca'          => true,
                        'newWindow'    => true,
                        'tokenEnabled' => true,
                        'tokenCount'   => $tokensToShowOnGrid,
                        'max_tokens'   => 2
                    ],
                ]
            ],
            $this->piConfigProviderModel->getConfig()
        );
    }

    public function testMethodNotAvailable()
    {
        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $piModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\PI')
            ->disableOriginalConstructor()
            ->getMock();
        $piModelMock->expects($this->once())
            ->method('isAvailable')
            ->willReturn(false);

        $paymentHelperMock = $this
            ->getMockBuilder('Magento\Payment\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentHelperMock->expects($this->once())
            ->method('getMethodInstance')
            ->willReturn($piModelMock);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->piConfigProviderModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\ConfigProvider\PI',
            [
                "paymentHelper" => $paymentHelperMock,
                "storeManager"  => $this->getStoreManagerMock(),
            ]
        );

        $this->assertEquals([], $this->piConfigProviderModel->getConfig());
    }

    /**
     * @return \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getStoreManagerMock()
    {
        $storeMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->once())->method("getId")->willReturn(4);
        $storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeManagerMock->expects($this->once())->method("getStore")->willReturn($storeMock);

        return $storeManagerMock;
    }
}
