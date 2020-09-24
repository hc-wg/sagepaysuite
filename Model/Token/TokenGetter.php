<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 2020-09-17
 * Time: 16:40
 */

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Api\Data\ResultInterface;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

class TokenGetter
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

    /**
     * VaultDetailsHandler constructor.
     * @param Logger $suiteLogger
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Json $jsonSerializer
     */
    public function __construct(
        Logger $suiteLogger,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Json $jsonSerializer,
        ResultInterface $result
    ) {
        $this->suiteLogger            = $suiteLogger;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenFactory    = $paymentTokenFactory;
        $this->jsonSerializer         = $jsonSerializer;
        $this->result                 = $result;

    }

    /**
     * @param string $tokenId
     * @param string $customerId
     * @return ResultInterface
     */
    public function getSagePayToken($tokenId, $customerId)
    {
        //TO DO:
        //Podria ser necesario encriptar el getaway token cuando se guarda y desencriptarlo al hacer el get
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
}
