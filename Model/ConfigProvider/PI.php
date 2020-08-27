<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\ConfigProvider;

use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\PI as PiModel;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;
use Magento\Store\Model\StoreManagerInterface;

class PI extends CcGenericConfigProvider
{

    /**
     * @var string
     */
    private $methodCode = Config::METHOD_PI;

    /**
     * @var PiModel
     */
    private $method;

    /**
     * @var Data
     */
    private $_suiteHelper;

    /**
     * @var Config
     */
    private $_config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Session */
    private $_customerSession;

    /**
     * PI constructor.
     * @param CcConfig $ccConfig
     * @param PaymentHelper $paymentHelper
     * @param Data $suiteHelper
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param Session $_customerSession
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        Data $suiteHelper,
        Config $config,
        StoreManagerInterface $storeManager,
        Session $_customerSession
    ) {
        parent::__construct($ccConfig, $paymentHelper);
        $this->_config          = $config;
        $this->storeManager     = $storeManager;
        $this->_suiteHelper     = $suiteHelper;
        $this->_customerSession = $_customerSession;

        $store = $this->storeManager->getStore();
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $config->setMethodCode($this->methodCode);
        $config->setConfigurationScopeId($store->getId());
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        if (!$this->method->isAvailable()) {
            return [];
        }

        //get tokens if enabled and cutomer is logged in
        $tokenEnabled = (bool)$this->_config->isTokenEnabled();
        $tokens = null;
        if ($tokenEnabled) {
            if (!empty($this->_customerSession->getCustomerId())) {
//                $tokens = $this->_tokenModel->getCustomerTokens(
//                    $this->_customerSession->getCustomerId(),
//                    $this->_config->getVendorname()
//                );
                $tokenEnabled = true;
            } else {
                $tokenEnabled = false;
            }
        }

        return [
            'payment' => [
                'ebizmarts_sagepaysuitepi' => [
                    'licensed'     => $this->_suiteHelper->verify(),
                    'mode'         => $this->_config->getMode(),
                    'sca'          => $this->_config->shouldUse3dV2(),
                    'dropin'       => $this->_config->setMethodCode($this->methodCode)->dropInEnabled(),
                    'newWindow'    => $this->_config->get3dNewWindow(),
                    'tokenEnabled' => $tokenEnabled
                ]
            ]
        ];
    }
}
