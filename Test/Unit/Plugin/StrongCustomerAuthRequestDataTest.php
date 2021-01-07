<?php
namespace Ebizmarts\SagePaySuite\Test\Unit\Plugin;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;
use Ebizmarts\SagePaySuite\Model\PiRequest;
use Ebizmarts\SagePaySuite\Plugin\StrongCustomerAuthRequestData;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class StrongCustomerAuthRequestDataTest extends TestCase
{
    const STRONG_CUSTOMER_AUTHENTICATION_KEY = 'strongCustomerAuthentication';
    const USER_AGENT = "Mozilla\/5.0 (Macintosh; Intel Mac OS X 10.14; rv:68.0) Gecko\/20100101 Firefox\/68.0";
    const BROWSER_LANGUAGE = "en-US";
    const NOTIFICATION_URL = "https://website.example/sagepaysuite/pi/callback3Dv2";
    const SERVICE_PURCHASE = "GoodsAndServicePurchase";
    const WINDOW_SIZE = "Large";
    const REMOTE_IP = "127.0.0.1";
    const MULTIPLE_REMOTE_IP = "127.0.0.1, 2001:0db8:85a3:0000:0000:8a2e:0370:7334, 123.123.123.123";
    const ACCEPT_HEADER_ALL = "*\/*";
    const QUOTE_ID = 1;
    const ENCODED_QUOTE_ID = 'MDozOiswMXF3V0l1WFRLTDRra0wxUCtYSGgyQVdORUdWaXNPN3N5RUNEbzE,';

    private $objectManagerHelper;

    /** @var Config */
    private $configMock;

    /** @var Request */
    private $requestMock;

    /** @var PiRequest */
    private $subjectMock;

    /** @var PiRequest */
    private $piRequestMock;

    /** @var CryptAndCodeData */
    private $cryptAndCodeMock;

    /** @var UrlInterface */
    private $urlMock;

    /** @var StrongCustomerAuthRequestData */
    private $sut;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->requestMock = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->subjectMock = $this->getMockBuilder(PiRequest::class)->disableOriginalConstructor()->getMock();
        $this->piRequestMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\PiRequest::class)->disableOriginalConstructor()->getMock();
        $this->cryptAndCodeMock = $this->getMockBuilder(CryptAndCodeData::class)->disableOriginalConstructor()->getMock();
        $this->urlMock = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
    }

    public function testNotScaTransactionConfig()
    {
        $this->configMock->expects($this->once())->method('shouldUse3dV2')->willReturn(false);

        $this->sut = $this->objectManagerHelper->getObject(
            StrongCustomerAuthRequestData::class,
            [
                'sagepayConfig' => $this->configMock,
                'request'       => $this->requestMock,
                'coreUrl'       => $this->urlMock,
                'cryptAndCode'  => $this->cryptAndCodeMock
            ]
        );

        $result = $this->sut->afterGetRequestData($this->subjectMock, []);

        $this->assertEquals([], $result);
    }

    public function testScaTransaction()
    {
        $this->expectSCAValues();

        $this->sut = $this->objectManagerHelper->getObject(
            StrongCustomerAuthRequestData::class,
            [
                'sagepayConfig' => $this->configMock,
                'request' => $this->requestMock,
                'coreUrl' => $this->urlMock,
                'cryptAndCode' => $this->cryptAndCodeMock
            ]
        );

        $result = $this->sut->afterGetRequestData($this->subjectMock, []);

        $this->assertArrayHasKey(self::STRONG_CUSTOMER_AUTHENTICATION_KEY, $result);
        $this->assertEquals($this->getExpectedScaParameters(), $result[self::STRONG_CUSTOMER_AUTHENTICATION_KEY]);
    }

    public function testScaTransactionMultipleIps()
    {
        $expectedSCAValues['getClientIp'] = self::MULTIPLE_REMOTE_IP;

        $this->expectSCAValues($expectedSCAValues);

        $this->sut = $this->objectManagerHelper->getObject(
            StrongCustomerAuthRequestData::class,
            [
                'sagepayConfig' => $this->configMock,
                'request' => $this->requestMock,
                'coreUrl' => $this->urlMock,
                'cryptAndCode' => $this->cryptAndCodeMock
            ]
        );

        $result = $this->sut->afterGetRequestData($this->subjectMock, []);

        $this->assertArrayHasKey(self::STRONG_CUSTOMER_AUTHENTICATION_KEY, $result);
        $this->assertEquals($this->getExpectedScaParameters(), $result[self::STRONG_CUSTOMER_AUTHENTICATION_KEY]);
    }

    public function testScaTransactionSingleIpAndColorDepthChrome()
    {
        $expectedSCAValues['getColorDepth'] = 30;
        $this->expectSCAValues($expectedSCAValues);

        $this->sut = $this->objectManagerHelper->getObject(
            StrongCustomerAuthRequestData::class,
            [
                'sagepayConfig' => $this->configMock,
                'request' => $this->requestMock,
                'coreUrl' => $this->urlMock,
                'cryptAndCode' => $this->cryptAndCodeMock
            ]
        );

        $result = $this->sut->afterGetRequestData($this->subjectMock, []);

        $this->assertArrayHasKey(self::STRONG_CUSTOMER_AUTHENTICATION_KEY, $result);
        $this->assertEquals($this->getExpectedScaParameters(), $result[self::STRONG_CUSTOMER_AUTHENTICATION_KEY]);
    }

    /**
     * @return array
     */
    private function getExpectedScaParameters(): array
    {
        return [
            'browserJavascriptEnabled' => 1,
            'browserJavaEnabled'       => 1,
            'browserColorDepth'        => 24,
            'browserScreenHeight'      => 1080,
            'browserScreenWidth'       => 1920,
            'browserTZ'                => 180,
            'browserAcceptHeader'      => self::ACCEPT_HEADER_ALL,
            'browserIP'                => self::REMOTE_IP,
            'browserLanguage'          => self::BROWSER_LANGUAGE,
            'browserUserAgent'         => self::USER_AGENT,
            'notificationURL'          => self::NOTIFICATION_URL,
            'transType'                => self::SERVICE_PURCHASE,
            'challengeWindowSize'      => self::WINDOW_SIZE
        ];
    }

    /**
     * @param array $expectations
     */
    private function expectSCAValues($expectations = array())
    {
        $this->configMock->expects($this->once())->method('shouldUse3dV2')->willReturn(true);
        $this->configMock->expects($this->once())->method('getValue')
            ->with("challengewindowsize")->willReturn(self::WINDOW_SIZE);

        $this->requestMock = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->requestMock->expects($this->once())->method('getHeader')->with('Accept')->willReturn(self::ACCEPT_HEADER_ALL);
        $this->requestMock->expects($this->once())->method('getClientIp')
            ->willReturn(isset($expectations['getClientIp']) ? $expectations['getClientIp'] : self::REMOTE_IP);

        $this->urlMock->expects($this->once())->method('getUrl')
            ->with(
                "sagepaysuite/pi/callback3Dv2",
                ["_secure" => true, 'quoteId' => self::ENCODED_QUOTE_ID, 'saveToken' => true]
            )
            ->willReturn(self::NOTIFICATION_URL);

        $this->cryptAndCodeMock->expects($this->once())->method('encryptAndEncode')->with(self::QUOTE_ID)
            ->willReturn(self::ENCODED_QUOTE_ID);

        $cartMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->disableOriginalConstructor()->getMock();

        $this->piRequestMock->expects($this->once())->method('getJavaEnabled')->willReturn(1);
        $this->piRequestMock->expects($this->once())->method('getColorDepth')
            ->willReturn(isset($expectations['getColorDepth']) ? $expectations['getColorDepth'] : 24);
        $this->piRequestMock->expects($this->once())->method('getScreenHeight')->willReturn(1080);
        $this->piRequestMock->expects($this->once())->method('getScreenWidth')->willReturn(1920);
        $this->piRequestMock->expects($this->once())->method('getTimezone')->willReturn(180);
        $this->piRequestMock->expects($this->once())->method('getLanguage')->willReturn(self::BROWSER_LANGUAGE);
        $this->piRequestMock->expects($this->once())->method('getUserAgent')->willReturn(self::USER_AGENT);
        $this->piRequestMock->expects($this->once())->method('getSaveToken')->willReturn(true);

        $this->subjectMock->expects($this->once())->method('getRequest')->willReturn($this->piRequestMock);
        $this->subjectMock->expects($this->once())->method('getCart')->willReturn($cartMock);

        $cartMock->expects($this->once())->method('getId')->willReturn(self::QUOTE_ID);
    }
}
