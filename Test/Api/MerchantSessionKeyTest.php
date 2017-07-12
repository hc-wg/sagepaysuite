<?php

namespace Ebizmarts\SagePaySuite\Test\Integration;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;

class MerchantSessionKeyTest extends \Magento\TestFramework\TestCase\WebapiAbstract
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testMskCall()
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/sagepay/pi-msk',
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
        ];
        $response = $this->_webApiCall($serviceInfo, []);

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('response', $response);
        $this->assertRegExp("/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}$/", $response['response']);
    }
}