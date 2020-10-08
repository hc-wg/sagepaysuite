<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

class Save
{
    /** @var Logger */
    private $suiteLogger;

    /** @var PaymentTokenFactoryInterface */
    private $paymentTokenFactory;

    /** @var PaymentTokenRepositoryInterface */
    private $paymentTokenRepository;

    /** @var Json */
    private $jsonSerializer;

    /**
     * VaultDetailsHandler constructor.
     * @param Logger $suiteLogger
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Json $jsonSerializer
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     */
    public function __construct(
        Logger $suiteLogger,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Json $jsonSerializer,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->suiteLogger            = $suiteLogger;
        $this->paymentTokenFactory    = $paymentTokenFactory;
        $this->jsonSerializer         = $jsonSerializer;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    /**
     * @param Payment $payment
     * @param int $customerId
     * @param string $token
     *
     */
    public function saveToken($payment, $customerId, $token)
    {
        if (!empty($customerId)) {
            $paymentToken = $this->createVaultPaymentToken($payment, $customerId, $token);
            if ($paymentToken !== null) {
                $this->paymentTokenRepository->save($paymentToken);
            }
        }
    }

    /**
     * @param Payment $payment
     * @param int $customerId
     * @param string $token
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     */
    public function createVaultPaymentToken($payment, $customerId, $token)
    {
        if (empty($token)) {
            return null;
        }

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);
        $paymentToken->setTokenDetails($this->createTokenDetails($payment));
        $paymentToken->setCustomerId($customerId);
        $paymentToken->setPaymentMethodCode($payment->getMethod());
        $paymentToken->setPublicHash($this->generatePublicHash($token));
        $paymentToken->setIsVisible(true);
        $paymentToken->setIsActive(true);

        return $paymentToken;
    }

    /**
     * @param Payment $payment
     * @return string
     */
    private function createTokenDetails($payment)
    {
        $tokenDetails = [
            'type' => $payment->getCcType(),
            'maskedCC' => $payment->getCcLast4(),
            'expirationDate' => $payment->getCcExpMonth() . '/' . $payment->getCcExpYear()
        ];

        return $this->convertArrayToJSON($tokenDetails);
    }

    /**
     * @param array $array
     * @return string
     */
    private function convertArrayToJSON($array)
    {
        return $this->jsonSerializer->serialize($array);
    }

    /**
     * @param string $token
     * @return string
     */
    private function generatePublicHash($token)
    {
        return hash('md5', $token);
    }
}
