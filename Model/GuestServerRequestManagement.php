<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite;

class GuestServerRequestManagement extends ServerRequestManagement implements \Ebizmarts\SagePaySuite\Api\GuestServerManagementInterface
{

    /**
     * {@inheritDoc}
     */
    public function getQuoteById($cartId)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->quoteRepository->get($quoteIdMask->getQuoteId());
    }
}