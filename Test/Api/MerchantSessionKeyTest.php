<?php

namespace Ebizmarts\SagePaySuite\Test\Integration;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class MerchantSessionKeyTest extends WebapiAbstract
{
    const TEST_API_KEY = "snEEZ7EFaM5q9GzBspep";
    const TEST_API_PASSWORD = "MrzrB8u3CST4FLLNRXL6";
    const VALID_MERCHANT_SESSION_KEY = "/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}$/";

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var \Magento\Config\Model\Config */
    private $config;

    protected function setUp()
    {
        $this->config = Bootstrap::getObjectManager()->create(
            'Magento\Config\Model\Config'
        );

        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testMskCall()
    {
        $this->savePiKey();
        $this->savePiPassword();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/sagepay/pi-msk',
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
        ];
        $response = $this->_webApiCall($serviceInfo, []);

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('response', $response);
        $this->assertRegExp(self::VALID_MERCHANT_SESSION_KEY, $response['response']);
    }

    private function savePiKey()
    {
        $this->config->setDataByPath("payment/sagepaysuitepi/key", self::TEST_API_KEY);
        $this->config->save();
    }

    private function savePiPassword()
    {
        $model = $this->objectManager->create('Magento\Config\Model\Config\Backend\Encrypted');
        $model->setPath('payment/sagepaysuitepi/password');
        $model->setScopeId(0);
        $model->setScope('default');
        $model->setScopeCode('');
        $model->setValue(self::TEST_API_PASSWORD);
        $model->save();
    }
}
