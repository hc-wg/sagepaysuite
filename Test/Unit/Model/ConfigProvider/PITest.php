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

    // @codingStandardsIgnoreStart
    protected function setUp()
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

        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->piConfigProviderModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\ConfigProvider\PI',
            [
                "config" => $this->configMock,
                "paymentHelper" => $paymentHelperMock,
                'suiteHelper' => $suiteHelperMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testGetConfig()
    {
        $this->assertEquals(
            [
                'payment' => [
                    'ebizmarts_sagepaysuitepi' => [
                        'licensed' => null,
                        'mode' => null
                    ],
                ]
            ],
            $this->piConfigProviderModel->getConfig()
        );
    }
}
