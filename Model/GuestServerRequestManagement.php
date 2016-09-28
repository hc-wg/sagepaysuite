<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite;

use \Ebizmarts\SagePaySuite\Api\GuestServerManagementInterface;

class GuestServerRequestManagement extends ServerRequestManagement implements GuestServerManagementInterface
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
