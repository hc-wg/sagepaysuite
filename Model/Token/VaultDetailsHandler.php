<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Plugin\DeleteTokenFromSagePay;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
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
            $token = $this->tokenGet->getTokenById($tokenId);
            if ($token->getCustomerId() !== $customerId) {
                throw new AuthenticationException(
                    __('Unable to delete token from Opayo: customer does not own the token')
                );
            }
            $this->deleteTokenFromSagePay->deleteFromSagePay($token->getGatewayToken());
            return $this->tokenDelete->removeTokenFromVault($token);
        } catch (AuthenticationException | NoSuchEntityException $e) {
            $this->suiteLogger->logException($e);
            return false;
        }
    }

    /**
     * Once we move server to use Vault, this function will not be needed.
     *
     * @param int $customerId
     * @return array
     */
    public function getTokensFromCustomerToShowOnAccount($customerId)
    {
        $vaultTokens = $this->tokenGet->getTokensFromCustomerToShowOnGrid($customerId);
        $tokens = [];
        foreach ($vaultTokens as $token) {
            $token['isVault'] = true;
            $tokens[] = $token;
        }

        return $tokens;
    }
}
