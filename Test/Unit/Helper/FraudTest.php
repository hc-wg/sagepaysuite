<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Helper;


class FraudTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Fraud
     */
    protected $fraudHelperModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Reporting|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportingApiMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    protected function setUp()
    {
        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->reportingApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Reporting')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->fraudHelperModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Fraud',
            [
                "config" => $this->configMock,
                "reportingApi" => $this->reportingApiMock
            ]
        );
    }

    /**
     * @dataProvider processFraudInformationDataProvider
     */
    public function testProcessFraudInformation($data)
    {
        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->atLeastOnce())
            ->method('getAdditionalInformation')
            ->willReturn($data['payment_mode']);

        if ($data['payment_mode'] == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            $this->reportingApiMock->expects($this->once())
                ->method('getFraudScreenDetail')
                ->will($this->returnValue((object)[
                    "errorcode" => "0000",
                    "fraudscreenrecommendation" => $data['fraudscreenrecommendation'],
                    "fraudid" => "12345",
                    "fraudcode" => "765",
                    "fraudcodedetail" => "Fraud card",
                    "fraudprovidername" => "ReD",
                    "rules" => ""
                ]));

            $orderMock = $this
                ->getMockBuilder('Magento\Sales\Model\Order')
                ->disableOriginalConstructor()
                ->getMock();

            $paymentMock->expects($this->any())
                ->method('getOrder')
                ->willReturn($orderMock);

            $this->configMock->expects($this->once())
                ->method('getAutoInvoiceFraudPassed')
                ->willReturn($data['getAutoInvoiceFraudPassed']);
        }

        $transactionMock->expects($this->any())
            ->method('getTxnType')
            ->willReturn(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);

        $this->assertEquals(
            $data['expects'],
            $this->fraudHelperModel->processFraudInformation($transactionMock, $paymentMock)
        );
    }

    public function processFraudInformationDataProvider()
    {
        return [
            'test test' => [
                [
                    'payment_mode' => \Ebizmarts\SagePaySuite\Model\Config::MODE_TEST,
                    'expects' => [
                        'VPSTxId' => null,
                        'Action' => 'Marked as TEST',
                    ]
                ]
            ],
            'test live reject' => [
                [
                    'payment_mode' => \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE,
                    'fraudscreenrecommendation' => \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT,
                    'getAutoInvoiceFraudPassed' => false,
                    'expects' => [
                        'VPSTxId' => null,
                        'fraudscreenrecommendation' => 'REJECT',
                        'fraudid' => '12345',
                        'fraudcode' => '765',
                        'fraudcodedetail' => 'Fraud card',
                        'fraudprovidername' => 'ReD',
                        'fraudrules' => '',
                        'Action' => 'Marked as FRAUD.'
                    ]
                ]
            ],
            'test live ok' => [
                [
                    'payment_mode' => \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE,
                    'fraudscreenrecommendation' => \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_OK,
                    'getAutoInvoiceFraudPassed' => true,
                    'expects' => [
                        'VPSTxId' => null,
                        'fraudscreenrecommendation' => 'OK',
                        'fraudid' => '12345',
                        'fraudcode' => '765',
                        'fraudcodedetail' => 'Fraud card',
                        'fraudprovidername' => 'ReD',
                        'fraudrules' => '',
                        'Action' => 'Captured online, invoice # generated.'
                    ]
                ]
            ]
        ];
    }
}