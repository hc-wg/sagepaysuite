<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Payment\Helper\Data as PaymentHelper;
//use Magento\Paypal\Helper\Data as PaypalHelper;

class SagePayConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var string[]
     */
    protected $methodCodes = [
        Config::METHOD_FORM
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @param ConfigFactory $configFactory
     * @param ResolverInterface $localeResolver
     * @param CurrentCustomer $currentCustomer
     * @param PaypalHelper $paypalHelper
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        ConfigFactory $configFactory,
        ResolverInterface $localeResolver,
        CurrentCustomer $currentCustomer,
        PaymentHelper $paymentHelper
    ) {
        $this->localeResolver = $localeResolver;
        $this->config = $configFactory->create();
        $this->currentCustomer = $currentCustomer;
        $this->paymentHelper = $paymentHelper;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'sagepaysuite' => []
            ]
        ];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['sagepaysuite']['redirectUrl'][$code] = $this->getMethodRedirectUrl($code);
            }
        }
        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @param string $code
     * @return mixed
     */
    protected function getMethodRedirectUrl($code)
    {
        return $this->methods[$code]->getCheckoutRedirectUrl();
    }
}
