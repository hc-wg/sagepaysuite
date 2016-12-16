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
    private $fraudHelperModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Reporting|\PHPUnit_Framework_MockObject_MockObject
     */
    private $reportingApiMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    // @codingStandardsIgnoreStart
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
    // @codingStandardsIgnoreEnd

    public function testProcessFraudInformationNoResult()
    {
        /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\FraudScreenResponseInterface $fraudResponse */
        $fraudResponse = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\FraudScreenResponse::class)
            ->setMethods(['setFraudScreenRecommendation'])
            ->disableOriginalConstructor()
            ->getMock();

        $fraudResponse->setErrorCode("0000");
        $fraudResponse->setFraudId("someid");
        $fraudResponse->setFraudCode("somecode");
        $fraudResponse->setFraudCodeDetail("somedetail");
        $fraudResponse->setFraudProviderName("T3M");
        $fraudResponse->setThirdmanAction("NORESULT");
        $fraudResponse->setThirdmanRules([]);

        $this->reportingApiMock->expects($this->once())->method('getFraudScreenDetail')->willReturn($fraudResponse);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())->method('setAdditionalInformation')
            ->with("fraudscreenrecommendation", 'NORESULT')
            ->willReturnSelf();

        $return = $this->fraudHelperModel->processFraudInformation($transactionMock, $paymentMock);

        $this->assertCount(2, $return);
        $this->assertArrayHasKey('fraudscreenrecommendation', $return);
        $this->assertEquals('NORESULT', $return['fraudscreenrecommendation']);
    }

    public function testProcessFraudInformationResponse()
    {
        $resp = new \stdClass();
        $resp->errorcode = "0010";
        $this->reportingApiMock->expects($this->once())->method('getFraudScreenDetail')->willReturn($resp);

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $return = $this->fraudHelperModel->processFraudInformation($transactionMock, $paymentMock);

        $this->assertArrayHasKey('ERROR', $return);
        $this->assertEquals('Invalid Response: 0010', $return['ERROR']);
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

        $fraudResponseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\FraudScreenResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($data['payment_mode'] == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            $this->reportingApiMock->expects($this->once())
                ->method('getFraudScreenDetail')
                ->willReturn($fraudResponseMock);
//                ->will($this->returnValue((object)[
//                    "errorcode" => "0000",
//                    "fraudscreenrecommendation" => $data['fraudscreenrecommendation'],
//                    "fraudid" => "12345",
//                    "fraudcode" => "765",
//                    "fraudcodedetail" => "Fraud card",
//                    "fraudprovidername" => "ReD",
//                    "rules" => ""
//                ]));

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
            'test live thirdman' => [
                [
                    'payment_mode' => \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE,
                    'fraudscreenrecommendation' => \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT,
                    'getAutoInvoiceFraudPassed' => false,
                    'expects' => [
                        'fraudprovidername' => 'T3M',
                        't3mid' => '4985075328',
                        't3mscore' => '37',
                        't3maction' => 'HOLD',
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
            ],
            'test live no result' => [
                [
                    'payment_mode' => 'test',
                    'fraudscreenrecommendation' => 'NORESULT',
                    'getAutoInvoiceFraudPassed' => false,
                    'expects' => [
                        'VPSTxId' => null,
                        'Action'  => 'Marked as TEST',
                    ]
                ]
            ]
        ];
    }
}
