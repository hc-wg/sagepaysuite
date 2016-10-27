<?php

namespace Ebizmarts\SagePaySuite\Model;

class GuestPayPalRequestManagement extends PayPalRequestManagement implements \Ebizmarts\SagePaySuite\Api\GuestPayPalManagementInterface
{
    /**
     * {@inheritDoc}
     */
    public function getQuoteById($cartId)
    {
        $quoteIdMask = $this->getQuoteIdMaskFactory()->create()->load($cartId, 'masked_id');

        return $this->getQuoteRepository()->get($quoteIdMask->getQuoteId());
    }
}
