<?php

namespace Ebizmarts\SagePaySuite\Test\API;

use Magento\TestFramework\Helper\Bootstrap;

class Helper
{
    const TEST_API_KEY = "snEEZ7EFaM5q9GzBspep";
    const TEST_API_PASSWORD = "MrzrB8u3CST4FLLNRXL6";

    /** @var \Magento\Config\Model\Config */
    private $config;

    public function __construct()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->config = $this->objectManager->create('Magento\Config\Model\Config');
    }

    public function savePiKey()
    {
        $this->config->setDataByPath("payment/sagepaysuitepi/key", self::TEST_API_KEY);
        $this->config->save();
    }

    public function savePiPassword()
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