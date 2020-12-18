<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Api\Post;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Plugin\DeleteTokenFromSagePay;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Sage Pay Token class
 */
class Token extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @var Post
     */
    private $_postApi;

    /**
     * @var Config
     */
    private $_config;

    /**
     * @var DeleteTokenFromSagePay
     */
    private $deleteTokenFromSagePay;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Logger $suiteLogger
     * @param Api\Post $postApi
     * @param Config $config
     * @param DeleteTokenFromSagePay $deleteTokenFromSagePay
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Logger $suiteLogger,
        Post $postApi,
        Config $config,
        DeleteTokenFromSagePay $deleteTokenFromSagePay,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_suiteLogger           = $suiteLogger;
        $this->_logger                = $context->getLogger();
        $this->_postApi               = $postApi;
        $this->_config                = $config;
        $this->deleteTokenFromSagePay = $deleteTokenFromSagePay;
    }

    /**
     * Init model
     *
     * @return void
     */
    // @codingStandardsIgnoreStart
    protected function _construct()
    {
        $this->_init('Ebizmarts\SagePaySuite\Model\ResourceModel\Token');
    }
    // @codingStandardsIgnoreEnd

    /**
     * Saves a token to the db
     *
     * @param $customerId
     * @param $token
     * @param $ccType
     * @param $ccLast4
     * @param $ccExpMonth
     * @param $ccExpYear
     * @param $vendorname
     * @return $this
     */
    public function saveToken($customerId, $token, $ccType, $ccLast4, $ccExpMonth, $ccExpYear, $vendorname)
    {
        if (empty($customerId)) {
            return $this;
        }

        $this->setCustomerId($customerId);
        $this->setToken($token);
        $this->setCcType($ccType);
        $this->setCcLast4($ccLast4);
        $this->setCcExpMonth($ccExpMonth);
        $this->setCcExpYear($ccExpYear);
        $this->setVendorname($vendorname);
        $this->save();

        return $this;
    }

    /**
     * Gets an array of the tokens owned by a customer and for a certain vendorname
     *
     * @param $customerId
     * @param $vendorname
     * @return array
     */
    public function getCustomerTokens($customerId, $vendorname)
    {
        if (!empty($customerId)) {
            $this->setData([]);
            $this->getResource()->getCustomerTokens($this, $customerId, $vendorname);
            return $this->_data;
        }
        return [];
    }

    /**
     * @param $customerId
     * @param $vendorname
     * @return array
     */
    public function getCustomerTokensToShowOnAccount($customerId, $vendorname)
    {
        $tokens = [];
        $serverTokens = $this->getCustomerTokens($customerId, $vendorname);
        foreach ($serverTokens as $token) {
            $token['isVault'] = false;
            $tokens[] = $token;
        }
        return $tokens;
    }

    /**
     * Delete token from db and Sage Pay
     * @throws
     */
    public function deleteToken()
    {
        //delete from sagepay
        $this->deleteTokenFromSagePay->deleteFromSagePay($this->getToken());

        if ($this->getId()) {
            $this->delete();
        }
    }

    /**
     * load from db
     *
     * @param $tokenId
     * @return \Ebizmarts\SagePaySuite\Model\Token
     */
    public function loadToken($tokenId)
    {
        $token = $this->getResource()->getTokenById($tokenId);

        if ($token === null) {
            return null;
        }

        $this->setId($token["id"])
            ->setCustomerId($token["customer_id"])
            ->setToken($token["token"])
            ->setCcType($token["cc_type"])
            ->setCcLast4($token["cc_last_4"])
            ->setCcExpMonth($token["cc_exp_month"])
            ->setCcExpYear($token["cc_exp_year"])
            ->setVendorname($token["vendorname"])
            ->setCreatedAt($token["created_at"])
            ->setStoreId($token["store_id"]);

        return $this;
    }

    /**
     * Checks whether the token is owned by the customer
     *
     * @param $customerId
     * @return bool
     */
    public function isOwnedByCustomer($customerId)
    {
        if (empty($customerId) || empty($this->getId())) {
            return false;
        }
        return $this->getResource()->isTokenOwnedByCustomer($customerId, $this->getId());
    }

    /**
     * Checks whether the customer is using all the available token slots.
     * @param $customerId
     * @param $vendorname
     * @return bool
     */
    public function isCustomerUsingMaxTokenSlots($customerId, $vendorname)
    {
        if (empty($customerId)) {
            return true;
        }
        $this->setData([]);
        $this->getResource()->getCustomerTokens($this, $customerId, $vendorname);
        return count($this->_data) >= $this->_config->getMaxTokenPerCustomer();
    }
}
