<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

/**
 * Resource collection for report rows
 */
namespace Ebizmarts\SagePaySuite\Model\ResourceModel\FraudReport;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Mapping for fields
     *
     * @var array
     */
    protected $_map = [
        'fields' => [
            'additional_information' => 'payments.additional_information'
        ],
    ];

    /**
     * Resource initializing
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Magento\Sales\Model\Order\Payment\Transaction',
            'Magento\Sales\Model\ResourceModel\Order\Payment\Transaction'
        );
    }

    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->join(
            ['payments' => $this->getTable('sales_order_payment')],
            'payments.entity_id = main_table.payment_id'
        )->where("sagepaysuite_fraud_check=1");
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'created_at') {
            $field = 'main_table.' . $field;
        }

        return parent::addFieldToFilter($field, $condition);
    }
}
