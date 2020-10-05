<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

class VaultDetailsHandler
{
    /** @var Logger */
    private $suiteLogger;

    /** @var PaymentTokenManagementInterface */
    private $paymentTokenManagement;

    /** @var PaymentTokenFactoryInterface */
    private $paymentTokenFactory;

    /** @var PaymentTokenRepositoryInterface */
    private $paymentTokenRepository;

    /** @var Json */
    private $jsonSerializer;

    /**
     * VaultDetailsHandler constructor.
     * @param Logger $suiteLogger
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Json $jsonSerializer
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     */
    public function __construct(
        Logger $suiteLogger,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Json $jsonSerializer,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->suiteLogger            = $suiteLogger;
        $this->paymentTokenManagement = $paymentTokenManagement;
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
                //TO DO: investigate to replace tokenManagement with tokenRepository to save tokens
                $this->paymentTokenManagement->saveTokenWithPaymentLink($paymentToken, $payment);
            }
        }
    }

    /**
     * @param int $customerId
     * @return array
     */
    public function getTokensFromCustomersToShowOnGrid($customerId)
    {
        //getListByCustomerId says that return \Magento\Vault\Api\Data\PaymentTokenSearchResultsInterface[]
        //but actually return \Magento\Vault\Api\Data\PaymentTokenInterface[]
        $tokenList = $this->paymentTokenManagement->getListByCustomerId($customerId);
        $tokenListToShow = [];
        foreach ($tokenList as $token) {
            if ($token->getIsActive() && $token->getIsVisible()) {
                $tokenDetails = $this->convertJsonToArray($token->getTokenDetails());
                $data = [
                    'id' => $token->getEntityId(),
                    'customer_id' => $token->getCustomerId(),
                    'cc_last_4' => $tokenDetails['maskedCC'],
                    'cc_type' => $tokenDetails['type'],
                    'cc_exp_month' => substr($tokenDetails['expirationDate'], 0, 2),
                    'cc_exp_year' => substr($tokenDetails['expirationDate'], 3, 2)
                ];
                $tokenListToShow[] = $data;
            }
        }

        return $tokenListToShow;
    }

    /**
     * @param int $tokenId
     * @param int $customerId
     * @return string
     */
    public function getSagePayToken($tokenId, $customerId)
    {
        //TO DO:
        //Podria ser necesario encriptar el getaway token cuando se guarda y desencriptarlo al hacer el get
        $sagePayToken = "";
        $tokenList = $this->paymentTokenManagement->getListByCustomerId($customerId);
        foreach ($tokenList as $token) {
            if ($token->getEntityId() === $tokenId) {
                $sagePayToken = $token->getGatewayToken();
            }
        }

        return $sagePayToken;
    }

    /**
     * @param $tokenId
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    public function getTokenById($tokenId)
    {
        return $this->paymentTokenRepository->getById($tokenId);
    }

    /**
     * @param int $tokenId
     * @param int $customerId
     * @return bool
     */
    public function removeTokenFromVault($tokenId, $customerId)
    {
        $token = $this->getTokenById($tokenId);
        if ($token->getCustomerId() !== $customerId) {
            return false;
        }
        return $this->paymentTokenRepository->delete($token);
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

        /**
         * TO DO:
         * Revisar si en gateway token se debe guardar el token y si el hash esta bien.
         */
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

    private function generatePublicHash($token)
    {
        return hash('md5', $token);
    }

    /**
     * @param string $string
     * @return array
     */
    private function convertJsonToArray($string)
    {
        return $this->jsonSerializer->unserialize($string);
    }
}
