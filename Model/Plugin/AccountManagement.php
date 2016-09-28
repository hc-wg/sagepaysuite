<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Plugin;

class AccountManagement
{
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $_quoteFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * AccountManagement constructor.
     *
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
    
        $this->_quoteFactory = $quoteFactory;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Customer\Model\AccountManagement $accountManagement
     * @param \Closure $proceed
     * @param $customerEmail
     * @param null $websiteId
     * @return mixed
     */
    public function aroundIsEmailAvailable(
        \Magento\Customer\Model\AccountManagement $accountManagement, // @codingStandardsIgnoreLine
        \Closure $proceed,
        $customerEmail,
        $websiteId = null
    ) {
    
        $ret = $proceed($customerEmail,$websiteId);
        if ($this->_checkoutSession) {
            $quoteId = $this->_checkoutSession->getQuoteId();
            if ($quoteId) {
                $quote = $this->_quoteFactory->create()->load($quoteId);
                $quote->setCustomerEmail($customerEmail);
                $quote->setUpdatedAt(date('Y-m-d H:i:s'));
                $quote->save();
            }
        }
        return $ret;
    }
}
