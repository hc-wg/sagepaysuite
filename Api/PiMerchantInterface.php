<?php

namespace Ebizmarts\SagePaySuite\Api;

/**
 * @api
 */
interface PiMerchantInterface
{

    /**
     * Creates a merchant session key (MSK).
     *
     * @param \Magento\Quote\Model\Quote $quote $quote
     * @return \Ebizmarts\SagePaySuite\Api\Data\ResultInterface
     */
    public function getSessionKey(\Magento\Quote\Model\Quote $quote = null);
}
