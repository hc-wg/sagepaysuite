<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Customer;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Token;
use Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Block to display customer tokens in customer area
 */
class TokenList extends Template
{
    /**
     * @var CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var Config
     */
    private $_config;

    /**
     * @var Token
     */
    private $_tokenModel;

    /** @var VaultDetailsHandler */
    private $_vaultDetailsHandler;

    /**
     * @param Context $context
     * @param CurrentCustomer $currentCustomer
     * @param array $data
     */
    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        Config $config,
        VaultDetailsHandler $vaultDetailsHandler,
        Token $tokenModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->currentCustomer      = $currentCustomer;
        $this->_config              = $config;
        $this->_tokenModel          = $tokenModel;
        $this->_vaultDetailsHandler = $vaultDetailsHandler;

        $this->setItems(
            $this->getCustomerTokensToShow()
        );
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->getRefererUrl()) {
            return $this->getRefererUrl();
        }
        return $this->getUrl('customer/account/');
    }

    public function getMaxTokenPerCustomer()
    {
        return $this->_config->getMaxTokenPerCustomer();
    }

    /**
     * @return array
     */
    private function getCustomerTokensToShow()
    {
        $vaultTokens = $this->_vaultDetailsHandler->getTokensFromCustomerToShowOnAccount(
            $this->currentCustomer->getCustomerId()
        );

        $serverTokens = $this->_tokenModel->getCustomerTokensToShowOnAccount(
            $this->currentCustomer->getCustomerId(),
            $this->_config->getVendorname()
        );

        return array_merge($vaultTokens, $serverTokens);
    }
}
