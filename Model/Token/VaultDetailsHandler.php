<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Plugin\DeleteTokenFromSagePay;
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

    /** @var DeleteTokenFromSagePay */
    private $deleteTokenFromSagePay;

    /**
     * VaultDetailsHandler constructor.
     * @param Logger $suiteLogger
     * @param Save $tokenSave
     * @param Get $tokenGet
     * @param Delete $tokenDelete
     * @param DeleteTokenFromSagePay $deleteTokenFromSagePay
     */
    public function __construct(
        Logger $suiteLogger,
        Save $tokenSave,
        Get $tokenGet,
        Delete $tokenDelete,
        DeleteTokenFromSagePay $deleteTokenFromSagePay
    ) {
        $this->suiteLogger = $suiteLogger;
        $this->tokenSave   = $tokenSave;
        $this->tokenGet    = $tokenGet;
        $this->tokenDelete = $tokenDelete;
        $this->deleteTokenFromSagePay = $deleteTokenFromSagePay;
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
        $token = $this->tokenGet->getSagePayToken($tokenId);
        $this->deleteTokenFromSagePay->deleteFromSagePay($token);
        return $this->tokenDelete->removeTokenFromVault($tokenId, $customerId);
    }
}
