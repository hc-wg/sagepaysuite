<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Plugin;

class AccountManagement
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * AccountManagement constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_objectManager = $objectManager;
    }
    public function aroundIsEmailAvailable(\Magento\Customer\Model\AccountManagement $accountManagement,\Closure $proceed,$customerEmail,$websiteId=null)
    {
        $ret = $proceed($customerEmail,$websiteId);
        $session = $this->_getSession();
        if($session)
        {
            $quoteId = $session->getQuoteId();
            if($quoteId) {
                $quote = $this->_objectManager->get('\Magento\Quote\Model\Quote')->load($quoteId);
                $quote->setCustomerEmail($customerEmail);
                $quote->setUpdatedAt(date('Y-m-d H:i:s'));
                $quote->save();
            }
        }
        return $ret;
    }
    protected function _getSession()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }
}