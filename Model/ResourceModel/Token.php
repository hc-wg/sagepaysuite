<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\ResourceModel;

/**
 * Token resource model
 */
class Token extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sagepaysuite_token', 'id');
    }

    /**
     * Get tokens by customer id and vendorname
     */
    public function getCustomerTokens(\Ebizmarts\SagePaySuite\Model\Token $object, $customerId, $vendorname)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                $this->getMainTable()
            )->where(
                'customer_id=?',
                $customerId
            )->where(
                'vendorname=?',
                $vendorname
            );

        $data = $connection->fetchAll($select);

        if ($data) {
            $object->setData($data);
        }

        $this->_afterLoad($object);

        return $data;
    }

    /**
     * Checks if token is owned by customer
     *
     * @param $customerId
     * @param $tokenId
     * @return bool
     */
    public function isTokenOwnedByCustomer($customerId, $tokenId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                $this->getMainTable()
            )->where(
                'customer_id=?',
                $customerId
            )->where(
                'id=?',
                $tokenId
            );

        $data = $connection->fetchAll($select);

        if (count($data) == 1) {
            return true;
        }

        return false;
    }
}
