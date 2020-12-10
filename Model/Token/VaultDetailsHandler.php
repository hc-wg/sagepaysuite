<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Plugin\DeleteTokenFromSagePay;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Message\ManagerInterface;

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

    /** @var ManagerInterface */
    private $messageManager;

    /**
     * VaultDetailsHandler constructor.
     * @param Logger $suiteLogger
     * @param Save $tokenSave
     * @param Get $tokenGet
     * @param Delete $tokenDelete
     * @param DeleteTokenFromSagePay $deleteTokenFromSagePay
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Logger $suiteLogger,
        Save $tokenSave,
        Get $tokenGet,
        Delete $tokenDelete,
        DeleteTokenFromSagePay $deleteTokenFromSagePay,
        ManagerInterface $messageManager
    ) {
        $this->suiteLogger            = $suiteLogger;
        $this->tokenSave              = $tokenSave;
        $this->tokenGet               = $tokenGet;
        $this->tokenDelete            = $tokenDelete;
        $this->deleteTokenFromSagePay = $deleteTokenFromSagePay;
        $this->messageManager         = $messageManager;
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
        try {
            $token = $this->tokenGet->getSagePayToken($tokenId);
            $this->deleteTokenFromSagePay->deleteFromSagePay($token);
            return $this->tokenDelete->removeTokenFromVault($tokenId, $customerId);
        } catch (\Exception $e) {
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getMessage(), [__METHOD__, __LINE__]);
            $this->messageManager->addErrorMessage(__('Unable to delete token from Opayo: missing data to proceed'));
            return false;
        }
    }
}
