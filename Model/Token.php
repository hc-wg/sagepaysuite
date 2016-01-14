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

    public function saveToken($customerId,$token,$ccType,$ccLast4,$ccExpMonth,$ccExpYear,$vendorname){

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

    public function getCustomerTokens($customerId,$vendorname){
        if(!empty($customerId)){
            $this->setData([]);
            $this->getResource()->getCustomerTokens($this,$customerId,$vendorname);
            return $this->_data;
        }
        return null;
    }

}