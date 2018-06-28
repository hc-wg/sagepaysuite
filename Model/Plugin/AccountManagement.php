<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Plugin;

use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteFactory;

class AccountManagement
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * AccountManagement constructor.
     *
     * @param QuoteFactory $quoteFactory
     * @param Session $checkoutSession
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        Session $checkoutSession
    ) {
    
        $this->quoteFactory    = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Customer\Model\AccountManagement $accountManagement
     * @param \Closure $proceed
     * @param $customerEmail
     * @param null $websiteId
     * @return mixed
     */
    public function aroundIsEmailAvailable(
        \Magento\Customer\Model\AccountManagement $accountManagement,
        \Closure $proceed,
        $customerEmail,
        $websiteId = null
    ) {
        $accMgmnt = $accountManagement;
        $ret = $proceed($customerEmail,$websiteId);
        if ($this->checkoutSession) {
            $quoteId = $this->checkoutSession->getQuoteId();
            if ($quoteId) {
                $quote = $this->quoteFactory->create()->load($quoteId);
                $quote->setCustomerEmail($customerEmail);
                $quote->setUpdatedAt(date('Y-m-d H:i:s'));
                $quote->save();
            }
        }
        return $ret;
    }
}
