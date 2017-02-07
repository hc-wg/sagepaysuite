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
     * @return \Ebizmarts\SagePaySuite\Api\Data\ResultInterface
     */
    public function getSessionKey();
}
