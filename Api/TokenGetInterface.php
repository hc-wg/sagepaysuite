<?php

namespace Ebizmarts\SagePaySuite\Api;

use Ebizmarts\SagePaySuite\Api\Data\ResultInterface;

interface TokenGetInterface
{
    /**
     * @param $tokenId
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    public function getTokenById($tokenId);

    /**
     * @param int $customerId
     * @return \Magento\Vault\Api\Data\PaymentTokenSearchResultsInterface[]
     */
    public function getTokensFromCustomer($customerId);

    /**
     * @param int $customerId
     * @return array
     */
    public function getTokensFromCustomerToShowOnGrid($customerId);

    /**
     * @param string $tokenId
     * @return string
     */
    public function getSagePayToken($tokenId);

    /**
     * @param string $tokenId
     * @return Ebizmarts\SagePaySuite\Api\Data\ResultInterface
     */
    public function getSagePayTokenAsResultInterface($tokenId);
}
