<?php

namespace Ebizmarts\SagePaySuite\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

class VaultDetailsHandler
{
    /** @var Logger */
    private $suiteLogger;

    /** @var PaymentTokenManagementInterface */
    private $paymentTokenManagement;

    /** @var PaymentTokenFactoryInterface */
    private $paymentTokenFactory;

    /** @var Json */
    private $jsonSerializer;

    /**
     * VaultDetailsHandler constructor.
     * @param Logger $suiteLogger
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Json $jsonSeralizer
     */
    public function __construct(
        Logger $suiteLogger,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Json $jsonSeralizer
    ) {
        $this->suiteLogger            = $suiteLogger;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenFactory    = $paymentTokenFactory;
        $this->jsonSerializer         = $jsonSeralizer;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param $customerId
     * @param $token
     * @param $ccType
     * @param $ccLast4
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $vendorname
     */
    public function saveToken($payment, $customerId, $token, $ccType, $ccLast4, $ccExpMonth, $ccExpYear, $vendorname)
    {
        if (!empty($customerId)) {
            $paymentToken = $this->createVaultPaymentToken($token, $ccType, $ccLast4, $ccExpMonth, $ccExpYear, $vendorname);
            if ($paymentToken !== null) {
                $extensionAttributes = $payment->getExtensionAttributes();
                $extensionAttributes->setVaultPaymentToken($paymentToken);
            }
        }
    }

    /**
     * @param $token
     * @param $ccType
     * @param $ccLast4
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $vendorname
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     */
    public function createVaultPaymentToken($token, $ccType, $ccLast4, $ccExpMonth, $ccExpYear, $vendorname)
    {
        if (empty($token)) {
            return null;
        }

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);
        $paymentToken->setTokenDetails($this->createTokenDetails($ccType, $ccLast4, $ccExpMonth, $ccExpYear));

        return $paymentToken;
    }

    /**
     * @param $ccType
     * @param $ccLast4
     * @param $ccExpMonth
     * @param $ccExpYear
     * @return string
     */
    private function createTokenDetails($ccType, $ccLast4, $ccExpMonth, $ccExpYear)
    {
        $tokenDetails = [
            'type' => $ccType,
            'maskedCC' => $ccLast4,
            'expirationDate' => $ccExpMonth . '/' . $ccExpYear
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
}
