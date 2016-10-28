<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\ConfigProvider;

use Magento\Payment\Model\CcGenericConfigProvider;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use \Ebizmarts\SagePaySuite\Model\Config as Config;

class PI extends CcGenericConfigProvider
{

    /**
     * @var string
     */
    private $methodCode = Config::METHOD_PI;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form
     */
    private $method;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @param CcConfig $ccConfig
     * @param PaymentHelper $paymentHelper
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Config $config
    ) {
    
        parent::__construct($ccConfig, $paymentHelper);

        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_suiteHelper = $suiteHelper;
        $this->_config = $config;
    }

    /**
     * @return array|void
     */
    public function getConfig()
    {
        if (!$this->method->isAvailable()) {
            return [];
        }

        return [
            'payment' => [
                'ebizmarts_sagepaysuitepi' => [
                    'licensed' => $this->_suiteHelper->verify(),
                    'mode' => $this->_config->getMode()
                ],
            ]
        ];
    }
}
