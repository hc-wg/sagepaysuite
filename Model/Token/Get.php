<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Api\Data\ResultInterface;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

class Get
{
    /** @var Logger */
    private $suiteLogger;

    /** @var PaymentTokenManagementInterface */
    private $paymentTokenManagement;

    /** @var PaymentTokenFactoryInterface */
    private $paymentTokenFactory;

    /** @var Json */
    private $jsonSerializer;

    /** @var ResultInterface */
    private $result;

    /** @var PaymentTokenRepositoryInterface */
    private $paymentTokenRepository;

    /**
     * VaultDetailsHandler constructor.
     * @param Logger $suiteLogger
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Json $jsonSerializer
     * @param ResultInterface $result
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     */
    public function __construct(
        Logger $suiteLogger,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Json $jsonSerializer,
        ResultInterface $result,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->suiteLogger            = $suiteLogger;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenFactory    = $paymentTokenFactory;
        $this->jsonSerializer         = $jsonSerializer;
        $this->result                 = $result;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    /**
     * @param $tokenId
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    public function getTokenById($tokenId)
    {
        return $this->paymentTokenRepository->getById($tokenId);
    }

//    TO DO: Implementar con paymentTokenRepository->getList
//    public function getTokensFromCustomer($customerId)
//    {
//
//    }

    /**
     * @param int $customerId
     * @return array
     */
    public function getTokensFromCustomerToShowOnGrid($customerId)
    {
        //getListByCustomerId says that return \Magento\Vault\Api\Data\PaymentTokenSearchResultsInterface[]
        //but actually return \Magento\Vault\Api\Data\PaymentTokenInterface[]

        // TO DO: Use paymentTokenRepository->getList instead of paymentTokenManagement.
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
     * @param string $tokenId
     * @param string $customerId
     * @return ResultInterface
     */
    public function getSagePayToken($tokenId, $customerId)
    {
        $this->suiteLogger->sageLog(Logger::LOG_REQUEST, 'flag token', [__METHOD__, __LINE__]);
        $this->result->setSuccess(false);
        $tokenList = $this->paymentTokenManagement->getListByCustomerId($customerId);
        foreach ($tokenList as $token) {
            if ($token->getEntityId() === $tokenId) {
                $this->result->setSuccess(true);
                $this->result->setResponse($token->getGatewayToken());
            }
        }
        $this->suiteLogger->sageLog(Logger::LOG_REQUEST, $this->result, [__METHOD__, __LINE__]);

        return $this->result;
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
