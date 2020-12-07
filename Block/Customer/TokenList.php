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
        $tokens = [];

        $vaultTokens = $this->_vaultDetailsHandler->getTokensFromCustomerToShowOnGrid(
            $this->currentCustomer->getCustomerId()
        );
        foreach ($vaultTokens as $token) {
            $token['isVault'] = true;
            $tokens[] = $token;
        }

        $serverTokens = $this->_tokenModel->getCustomerTokens(
            $this->currentCustomer->getCustomerId(),
            $this->_config->getVendorname()
        );
        foreach ($serverTokens as $token) {
            $token['isVault'] = false;
            $tokens[] = $token;
        }

        return $tokens;
    }
}
