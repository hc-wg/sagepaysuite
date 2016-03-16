<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\ConfigProvider;

use Magento\Checkout\Model\ConfigProviderInterface;
use \Ebizmarts\SagePaySuite\Model\Config as Config;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\Escaper;

class Paypal implements ConfigProviderInterface
{
    /**
     * @var string
     */
    protected $methodCode = Config::METHOD_PAYPAL;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form
     */
    protected $method;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $_suiteHelper;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
    )
    {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_suiteHelper = $suiteHelper;
    }

    public function getConfig()
    {
        if (!$this->method->isAvailable()) {
            return [];
        }

        return [
            'payment' => [
                'ebizmarts_sagepaysuitepaypal' => [
                    'licensed' => $this->_suiteHelper->verify(),
                    'mode' => $this->_suiteHelper->getSagePayConfig()->getMode()
                ],
            ]
        ];
    }
}