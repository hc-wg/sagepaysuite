<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\ConfigProvider;

class PITest extends \PHPUnit_Framework_TestCase
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
            ->expects($this->exactly(2))
            ->method('setMethodCode')
            ->with('sagepaysuitepi')
            ->willReturnSelf();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->piConfigProviderModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\ConfigProvider\PI',
            [
                "config"        => $this->configMock,
                "paymentHelper" => $paymentHelperMock,
                'suiteHelper'   => $suiteHelperMock
            ]
        );

        $this->assertEquals(
            [
                'payment' => [
                    'ebizmarts_sagepaysuitepi' => [
                        'licensed' => true,
                        'mode'     => 'test',
                        'dropin'   => true
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
                "paymentHelper" => $paymentHelperMock
            ]
        );

        $this->assertEquals([], $this->piConfigProviderModel->getConfig());
    }
}
