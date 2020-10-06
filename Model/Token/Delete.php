<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

class Delete
{
    /** @var Logger */
    private $suiteLogger;

    /** @var Get */
    private $tokenGet;

    /** @var PaymentTokenRepositoryInterface */
    private $paymentTokenRepository;

    public function __construct(
        Logger $suiteLogger,
        Get $tokenGet,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->suiteLogger            = $suiteLogger;
        $this->tokenGet               = $tokenGet;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    /**
     * @param int $tokenId
     * @param int $customerId
     * @return bool
     */
    public function removeTokenFromVault($tokenId, $customerId)
    {
        $token = $this->tokenGet->getTokenById($tokenId);
        if ($token->getCustomerId() !== $customerId) {
            return false;
        }
        return $this->paymentTokenRepository->delete($token);
    }

}
