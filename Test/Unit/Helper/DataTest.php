<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Helper;

namespace Ebizmarts\SagePaySuite\Test\Unit\Helper;

class DataTest extends \PHPUnit_Framework_TestCase
{
    private $objectManagerHelper;
    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Magento\Framework\Module\ModuleList\Loader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loaderMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->loaderMock = $this
            ->getMockBuilder('Magento\Framework\Module\ModuleList\Loader')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $dateTimeMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTime::class)
        ->setMethods(['gmtTimestamp', 'gmtDate'])
        ->disableOriginalConstructor()
        ->getMock();
        $dateTimeMock->expects($this->any())->method('gmtTimestamp')->willReturn('1456419355');
        $dateTimeMock->expects($this->any())->method('gmtDate')->willReturn('2016-02-25-085555');

        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->dataHelper = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Data',
            [
                'loader'   => $this->loaderMock,
                'config'   => $this->configMock,
                'dateTime' => $dateTimeMock,
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    /**
     * @dataProvider getVersionDataProvider
     */
    public function testGetVersion($data)
    {
        $this->loaderMock->expects($this->any())
            ->method('load')
            ->will($this->returnValue($data));

        $this->assertEquals(
            $data['expected'],
            $this->dataHelper->getVersion()
        );
    }

    public function getVersionDataProvider()
    {
        return [
            'test normal' => [
                [
                    'Ebizmarts_SagePaySuite' => [
                        'setup_version' => '1.0'
                    ],
                    'expected' => '1.0'
                ]
            ],
            'test not found' => [
                [
                    'expected' => 'UNKNOWN'
                ]
            ]
        ];
    }

    public function testClearTransactionId()
    {
        $uncleanTransactionId = self::TEST_VPSTXID . "-capture";

        $this->assertSame(
            self::TEST_VPSTXID,
            $this->dataHelper->clearTransactionId($uncleanTransactionId)
        );
    }

    /**
     * @dataProvider verifyDataProvider
     */
    public function testVerify($data)
    {
        $this->loaderMock->expects($this->any())
            ->method('load')
            ->will($this->returnValue($data));

        $this->configMock->expects($this->any())
            ->method('getStoreDomain')
            ->will($this->returnValue("http://www.example.com"));
        $this->configMock->expects($this->any())
            ->method('getLicense')
            ->will($this->returnValue("010b6116a7a99954fd2f3ad27e9706b2b5f5f51c"));

        $this->assertEquals(
            $data['expected'],
            $this->dataHelper->verify()
        );
    }

    public function verifyDataProvider()
    {
        return [
            'test normal' => [
                [
                    'Ebizmarts_SagePaySuite' => [
                        'setup_version' => '2.0'
                    ],
                    'expected' => false
                ]
            ],
            'test invalid' => [
                [
                    'Ebizmarts_SagePaySuite' => [
                        'setup_version' => '1.0'
                    ],
                    'expected' => true
                ]
            ]
        ];
    }

    /**
     * @dataProvider generateVendorTxCodeDataProvider
     */
    public function testGenerateVendorTxCode($data)
    {
        $this->assertEquals(
            $data['expected'],
            $this->dataHelper->generateVendorTxCode($data['order_id'], $data['action'])
        );
    }

    public function generateVendorTxCodeDataProvider()
    {
        return [
            'test PAYMENT' => [
                [
                    'order_id' => '1000000000001',
                    'action'   => \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT,
                    'expected' => '1000000000001-2016-02-25-085555145641935'
                ]
            ],
            'test REFUND' => [
                [
                    'order_id' => '1000000000002',
                    'action'   => \Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND,
                    'expected' => 'R1000000000002-2016-02-25-08555514564193'
                ]
            ],
            'test AUTHORISE' => [
                [
                    'order_id' => '1000000000004',
                    'action'   => 'AUTHORISE',
                    'expected' => 'A1000000000004-2016-02-25-08555514564193'
                ]
            ],
            'test REPEAT' => [
                [
                    'order_id' => '100000005687',
                    'action'   => 'REPEAT',
                    'expected' => 'RT100000005687-2016-02-25-08555514564193'
                ]
            ],
            'test REPEATDEFERRED' => [
                [
                    'order_id' => '000000004',
                    'action'   => 'REPEATDEFERRED',
                    'expected' => 'RT000000004-2016-02-25-0855551456419355'
                ]
            ]
        ];
    }

    public function testGetSagePayConfig()
    {
        $this->dataHelper = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Data',
            [
                'config'   => $this->configMock,
            ]
        );

        $this->assertInstanceOf('\Ebizmarts\SagePaySuite\Model\Config', $this->dataHelper->getSagePayConfig());
    }

    /**
     * @param $bool
     * @param $code
     * @dataProvider methodCodeIsSagePayProvider
     */
    public function testMethodCodeIsSagePay($bool, $code)
    {
        $this->dataHelper = $this->objectManagerHelper->getObject('Ebizmarts\SagePaySuite\Helper\Data');

        $this->assertEquals($bool, $this->dataHelper->methodCodeIsSagePay($code));
    }

    public function methodCodeIsSagePayProvider()
    {
        return [
            [true, 'sagepaysuiteform'],
            [false, 'authorize_net'],
            [true, 'sagepaysuiterepeat'],
            [true, 'sagepaysuiteserver'],
            [false, 'paypal'],
            [true, 'sagepaysuitepaypal'],
            [false, 'sagepaydirectpro'],
            [true, 'sagepaysuitepi']
        ];
    }
}
