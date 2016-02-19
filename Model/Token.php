<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

/**
 *
 */
class Token extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post
     */
    protected $_postApi;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

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
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Model\Api\Post $postApi,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_suiteLogger = $suiteLogger;
        $this->_logger = $context->getLogger();
        $this->_postApi = $postApi;
        $this->_config = $config;
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

        //delete from sagepay
        $this->_deleteFromSagePay();

        if($this->getId()){
            $this->delete();
        }
    }

    protected function _deleteFromSagePay()
    {
        try {

            if(empty($this->getVendorname()) || empty($this->getToken())){
                //missing data to proceed
                return;
            }

            //generate delete POST request
            $data = array();
            $data["VPSProtocol"] = $this->_config->getVPSProtocol();
            $data["TxType"] = "REMOVETOKEN";
            $data["Vendor"] = $this->getVendorname();
            $data["Token"] = $this->getToken();

            //send POST to Sage Pay
            $this->_postApi->sendPost($data,
                $this->getRemoveServiceURL(),
                array("OK")
            );

        }catch (\Exception $e)
        {
            $this->_logger->critical($e);
            //we do not show any error message to frontend
        }
    }

    public function getRemoveServiceURL(){
        if($this->_config->getMode()== \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE){
            return \Ebizmarts\SagePaySuite\Model\Config::URL_TOKEN_POST_REMOVE_LIVE;
        }else{
            return \Ebizmarts\SagePaySuite\Model\Config::URL_TOKEN_POST_REMOVE_TEST;
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