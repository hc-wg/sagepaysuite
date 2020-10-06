<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Sales\Model\Order\Payment;

class VaultDetailsHandler
{
    /** @var Logger */
    private $suiteLogger;

    /** @var Save */
    private $tokenSave;

    /** @var Get */
    private $tokenGet;

    /** @var Delete */
    private $tokenDelete;

    /**
     * VaultDetailsHandler constructor.
     * @param Logger $suiteLogger
     * @param Save $tokenSave
     * @param Get $tokenGet
     * @param Delete $tokenDelete
     */
    public function __construct(
        Logger $suiteLogger,
        Save $tokenSave,
        Get $tokenGet,
        Delete $tokenDelete
    ) {
        $this->suiteLogger = $suiteLogger;
        $this->tokenSave   = $tokenSave;
        $this->tokenGet    = $tokenGet;
        $this->tokenDelete = $tokenDelete;
    }

    /**
     * @param Payment $payment
     * @param int $customerId
     * @param string $token
     *
     */
    public function saveToken($payment, $customerId, $token)
    {
        $this->tokenSave->saveToken($payment, $customerId, $token);
    }

    /**
     * @param int $customerId
     * @return array
     */
    public function getTokensFromCustomerToShowOnGrid($customerId)
    {
        return $this->tokenGet->getTokensFromCustomerToShowOnGrid($customerId);
    }

    /**
     * @param int $tokenId
     * @param int $customerId
     * @return bool
     */
    public function deleteToken($tokenId, $customerId)
    {
        return $this->tokenDelete->removeTokenFromVault($tokenId, $customerId);
    }
}
