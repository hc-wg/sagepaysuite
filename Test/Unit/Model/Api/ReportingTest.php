<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Api;

class ReportingTest extends \PHPUnit_Framework_TestCase
{
    private $curlMockFactory;
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Reporting
     */
    private $reportingApiModel;

    /**
     * @var \Magento\Framework\HTTP\Adapter\Curl|\PHPUnit_Framework_MockObject_MockObject
     */
    private $curlMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apiExceptionFactoryMock;

    private $objectManagerMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->apiExceptionFactoryMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->curlMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\Adapter\Curl')
            ->disableOriginalConstructor()
            ->getMock();
        $this->curlMockFactory = $this->getMockBuilder('Magento\Framework\HTTP\Adapter\CurlFactory')
            ->setMethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->curlMockFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->curlMock));

        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManager\ObjectManager::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->reportingApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Reporting',
            [
                "curlFactory" => $this->curlMockFactory,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock,
                'objectManager' => $this->objectManagerMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testGetTransactionDetails()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '<vspaccess><errorcode>0000</errorcode><timestamp>04/11/2013 11:45:32</timestamp>
                <vpstxid>EE6025C6-7D24-4873-FB92-CD7A66B9494E</vpstxid><vendortxcode>REF20131029-1-838</vendortxcode>
                </vspaccess>'
            );

        $xmlWrite = 'XML=<vspaccess><command>getTransactionDetail</command><vendor></vendor><user></user>';
        $xmlWrite .= '<vpstxid>12345</vpstxid><signature>4a0787ba97d65455d24be4d1768133ac</signature></vspaccess>';
        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_REPORTING_API_TEST,
                '1.0',
                [],
                $xmlWrite
            );

        $xmldata = '<vspaccess><errorcode>0000</errorcode><timestamp>04/11/2013 11:45:32</timestamp>
                <vpstxid>EE6025C6-7D24-4873-FB92-CD7A66B9494E</vpstxid><vendortxcode>REF20131029-1-838</vendortxcode>
                </vspaccess>';
        $simpleInstance = new \SimpleXMLElement($xmldata);
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->reportingApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Reporting',
            [
                "curlFactory" => $this->curlMockFactory,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock,
                'objectManager' => $this->objectManagerMock
            ]
        );

        $this->assertEquals(
            (object)[
                "errorcode" => '0000',
                "timestamp" => '04/11/2013 11:45:32',
                "vpstxid" => 'EE6025C6-7D24-4873-FB92-CD7A66B9494E',
                "vendortxcode" => 'REF20131029-1-838'
            ],
            $this->reportingApiModel->getTransactionDetails("12345")
        );
    }

    public function testGetTransactionDetailsERROR()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '<vspaccess><errorcode>2015</errorcode><error>INVALID STATUS</error>
                <timestamp>04/11/2013 11:45:32</timestamp><vpstxid>EE6025C6-7D24-4873-FB92-CD7A66B9494E</vpstxid>
                <vendortxcode>REF20131029-1-838</vendortxcode></vspaccess>'
            );

        $xmlWrite  = 'XML=<vspaccess><command>getTransactionDetail</command><vendor></vendor><user></user>';
        $xmlWrite .= '<vpstxid>12345</vpstxid><signature>4a0787ba97d65455d24be4d1768133ac</signature></vspaccess>';

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_REPORTING_API_TEST,
                '1.0',
                [],
                $xmlWrite
            );

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException(
            new \Magento\Framework\Phrase("INVALID STATUS"),
            new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase("INVALID STATUS"))
        );

        $this->apiExceptionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($apiException));

        $xmldata = '<vspaccess><errorcode>2015</errorcode><error>INVALID STATUS</error>
                <timestamp>04/11/2013 11:45:32</timestamp><vpstxid>EE6025C6-7D24-4873-FB92-CD7A66B9494E</vpstxid>
                <vendortxcode>REF20131029-1-838</vendortxcode></vspaccess>';
        $simpleInstance = new \SimpleXMLElement($xmldata);
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->reportingApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Reporting',
            [
                "curlFactory" => $this->curlMockFactory,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock,
                'objectManager' => $this->objectManagerMock
            ]
        );

        try {
            $this->reportingApiModel->getTransactionDetails("12345");
            $this->assertTrue(false);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->assertEquals(
                "INVALID STATUS",
                $apiException->getUserMessage()
            );
        }
    }

    public function testGetTokenCount()
    {
        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '<vspaccess><errorcode>0000</errorcode><timestamp>04/11/2013 11:45:32</timestamp>
                <vpstxid>EE6025C6-7D24-4873-FB92-CD7A66B9494E</vpstxid><vendortxcode>REF20131029-1-838</vendortxcode>
                </vspaccess>'
            );

        $xmlWrite = 'XML=<vspaccess><command>getTokenCount</command><vendor></vendor><user></user>';
        $xmlWrite .= '<signature>eca0a57c18e960a6cba53f685597b6c2</signature></vspaccess>';

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_REPORTING_API_TEST,
                '1.0',
                [],
                $xmlWrite
            );

        $xmldata = '<vspaccess><errorcode>0000</errorcode><timestamp>04/11/2013 11:45:32</timestamp>
                <vpstxid>EE6025C6-7D24-4873-FB92-CD7A66B9494E</vpstxid><vendortxcode>REF20131029-1-838</vendortxcode>
                </vspaccess>';
        $simpleInstance = new \SimpleXMLElement($xmldata);
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->reportingApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Reporting',
            [
                "curlFactory" => $this->curlMockFactory,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock,
                'objectManager' => $this->objectManagerMock
            ]
        );

        $this->assertEquals(
            (object)[
                "errorcode" => '0000',
                "timestamp" => '04/11/2013 11:45:32',
                "vpstxid" => 'EE6025C6-7D24-4873-FB92-CD7A66B9494E',
                "vendortxcode" => 'REF20131029-1-838'
            ],
            $this->reportingApiModel->getTokenCount()
        );
    }

    public function testGetFraudScreenDetailRed()
    {
        $fraudResponseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\FraudScreenResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['setThirdmanAction']) //This is so all other methods are not mocked.
            ->getMock();

        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '<vspaccess><errorcode>0000</errorcode><timestamp>04/11/2013 11:45:32</timestamp>
                <vpstxid>EE6025C6-7D24-4873-FB92-CD7A66B9494E</vpstxid><vendortxcode>REF20131029-1-838</vendortxcode>
                </vspaccess>'
            );

        $xmlWrite = 'XML=<vspaccess><command>getFraudScreenDetail</command><vendor></vendor><user></user>';
        $xmlWrite .= '<vpstxid>12345</vpstxid><signature>85bd7f80aad73ecd5740bd6b58142071</signature></vspaccess>';

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_REPORTING_API_TEST,
                '1.0',
                [],
                $xmlWrite
            );

        $xmldata = '<vspaccess>
                        <errorcode>0000</errorcode>
                        <timestamp/>
                        <fraudprovidername>ReD</fraudprovidername>
                        <fraudscreenrecommendation>ACCEPT</fraudscreenrecommendation>
                        <fraudid/>
                        <fraudcode>0100</fraudcode>
                        <fraudcodedetail>Accept</fraudcodedetail>
                    </vspaccess>';
        $simpleInstance = new \SimpleXMLElement($xmldata);
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->reportingApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Reporting',
            [
                "curlFactory"         => $this->curlMockFactory,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock,
                "objectManager"       => $this->objectManagerMock,
                "fraudResponse"       => $fraudResponseMock
            ]
        );

        $response = $this->reportingApiModel->getFraudScreenDetail("12345");

        $this->assertEquals('0000', $response->getErrorCode());
        $this->assertEquals('', $response->getTimestamp());
        $this->assertEquals('ReD', $response->getFraudProviderName());
        $this->assertEquals('ACCEPT', $response->getFraudScreenRecommendation());
        $this->assertEquals('', $response->getFraudId());
        $this->assertEquals('0100', $response->getFraudCode());
        $this->assertEquals('Accept', $response->getFraudCodeDetail());
    }

    public function testGetFraudScreenDetailThirdman()
    {
        $fraudResponseMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\FraudScreenResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['setFraudId']) //This is so all other methods are not mocked.
            ->getMock();

        $fraudResponseFactoryMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\FraudScreenResponseInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create']) //This is so all other methods are not mocked.
            ->getMock();
        $fraudResponseFactoryMock->expects($this->once())->method('create')->willReturn($fraudResponseMock);

        $fraudResponseRuleMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\FraudScreenRule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomAttribute']) //This is so all other methods are not mocked.
            ->getMock();
        $fraudResponseRuleFactoryMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\SagePayData\FraudScreenRuleInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create']) //This is so all other methods are not mocked.
            ->getMock();
        $fraudResponseRuleFactoryMock->expects($this->exactly(2))->method('create')->willReturn($fraudResponseRuleMock);

        $this->curlMock->expects($this->once())
            ->method('read')
            ->willReturn(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '<vspaccess><errorcode>0000</errorcode><timestamp>04/11/2013 11:45:32</timestamp>
                <vpstxid>EE6025C6-7D24-4873-FB92-CD7A66B9494E</vpstxid><vendortxcode>REF20131029-1-838</vendortxcode>
                </vspaccess>'
            );

        $xmlWrite = 'XML=<vspaccess><command>getFraudScreenDetail</command><vendor></vendor><user></user>';
        $xmlWrite .= '<vpstxid>12345</vpstxid><signature>85bd7f80aad73ecd5740bd6b58142071</signature></vspaccess>';

        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                \Zend_Http_Client::POST,
                \Ebizmarts\SagePaySuite\Model\Config::URL_REPORTING_API_TEST,
                '1.0',
                [],
                $xmlWrite
            );

        $xmldata = '<vspaccess>
                        <errorcode>0000</errorcode>
                        <timestamp>30/11/2016 09:55:01</timestamp>
                        <fraudprovidername>T3M</fraudprovidername>
                        <t3mid>4985075328</t3mid>
                        <t3mscore>37</t3mscore>
                        <t3maction>HOLD</t3maction>
                        <t3mresults>
                            <rule>
                                <description>Telephone number is a mobile number</description>
                                <score>4</score>
                            </rule>
                            <rule>
                                <description>No Match on Electoral Roll, or Electoral Roll not available at billing address</description>
                                <score>10</score>
                            </rule>
                        </t3mresults>
                </vspaccess>';
        $simpleInstance = new \SimpleXMLElement($xmldata);
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->reportingApiModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Api\Reporting',
            [
                "curlFactory"         => $this->curlMockFactory,
                "apiExceptionFactory" => $this->apiExceptionFactoryMock,
                "objectManager"       => $this->objectManagerMock,
                "fraudResponse"       => $fraudResponseFactoryMock,
                "fraudScreenRule"     => $fraudResponseRuleFactoryMock
            ]
        );

        $response = $this->reportingApiModel->getFraudScreenDetail("12345");

        $this->assertEquals('0000', $response->getErrorCode());
        $this->assertEquals('30/11/2016 09:55:01', $response->getTimestamp());
        $this->assertEquals('T3M', $response->getFraudProviderName());
        $this->assertEquals('4985075328', $response->getThirdmanId());
        $this->assertEquals('37', $response->getThirdmanScore());
        $this->assertEquals('HOLD', $response->getThirdmanAction());
        $this->assertCount(2, $response->getThirdmanRules());

        $firstRule = current($response->getThirdmanRules());
        $this->assertEquals('10', $firstRule->getScore());
        $this->assertEquals(
            'No Match on Electoral Roll, or Electoral Roll not available at billing address',
            $firstRule->getDescription()
        );
    }
}
