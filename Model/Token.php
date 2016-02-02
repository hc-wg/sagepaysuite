<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

/**
 *
 */
class Token extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Init model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Ebizmarts\SagePaySuite\Model\ResourceModel\Token');
    }

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
    public function saveToken($customerId,$token,$ccType,$ccLast4,$ccExpMonth,$ccExpYear,$vendorname){

        if(empty($customerId)) {
            return $this;
        }

        $this->setCustomerId($customerId)
            ->setToken($token)
            ->setCcType($ccType)
            ->setCcLast4($ccLast4)
            ->setCcExpMonth($ccExpMonth)
            ->setCcExpYear($ccExpYear)
            ->setVendorname($vendorname)
            ->save();

        return $this;
    }

    /**
     * Gets an array of the tokens owned by a customer and for a certain vendorname
     *
     * @param $customerId
     * @param $vendorname
     * @return array
     */
    public function getCustomerTokens($customerId,$vendorname){
        if(!empty($customerId)){
            $this->setData([]);
            $this->getResource()->getCustomerTokens($this,$customerId,$vendorname);
            return $this->_data;
        }
        return array();
    }

    /**
     * Delete token from db
     *
     * @param $tokenId
     * @return void
     */
    public function deleteToken(){
        if($this->getId()){
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

        if(is_null($token)){
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
     * @param $tokenId
     * @return bool
     */
    public function isOwnedByCustomer($customerId){
        if(empty($customerId) || empty($this->getId())){
            return false;
        }
        return $this->getResource()->isTokenOwnedByCustomer($customerId, $this->getId());
    }

    /**
     * Checks whether the customer is using all the available token slots
     *
     * @param $customerId
     * @return bool
     */
    public function isCustomerUsingMaxTokenSlots($customerId,$vendorname){
        if(empty($customerId)){
            return true;
        }
        $this->setData([]);
        $this->getResource()->getCustomerTokens($this,$customerId,$vendorname);
        return count($this->_data) >= \Ebizmarts\SagePaySuite\Model\Config::MAX_TOKENS_PER_CUSTOMER;
    }

}