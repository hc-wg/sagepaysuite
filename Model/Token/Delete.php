<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

class Delete
{
    /** @var Logger */
    private $suiteLogger;

    /** @var Get */
    private $tokenGet;

    /** @var PaymentTokenRepositoryInterface */
    private $paymentTokenRepository;

    /**
     * Delete constructor.
     * @param Logger $suiteLogger
     * @param Get $tokenGet
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     */
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
     * @param PaymentTokenInterface $token
     * @return bool
     */
    public function removeTokenFromVault($token)
    {
        return $this->paymentTokenRepository->delete($token);
    }

}
