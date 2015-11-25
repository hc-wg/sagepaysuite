<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
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
    protected $methodCode = Config::METHOD_PI;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form
     */
    protected $method;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $_suiteHelper;

    /**
     * @param CcConfig $ccConfig
     * @param PaymentHelper $paymentHelper
     * @param \Magento\Braintree\Model\Vault $vault
     * @param \Magento\Braintree\Model\Config\Cc $config
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Url $urlBuilder
     * @param \Magento\Braintree\Helper\Data $dataHelper
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
    ) {
        parent::__construct($ccConfig, $paymentHelper);

        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_suiteHelper = $suiteHelper;
    }


    /**
     * @return array|void
     */
    public function getConfig()
    {
        if (!$this->method->isAvailable()) {
            return [];
        }

        return ['payment' => [
            'ebizmarts_sagepaysuitepi' => [
                'licensed' => $this->_suiteHelper->verify()
            ],
        ]
        ];
    }
}
