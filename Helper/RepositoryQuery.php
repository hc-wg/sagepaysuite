<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Helper;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use \Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\OrderRepository;

class RepositoryQuery extends AbstractHelper
{
    /**
     * @var FilterBuilder
     */
    private $_filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $_filterGroupBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $_searchCriteriaBuilder;


    public function __construct(
        Context $context,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository
    )
    {
        parent::__construct($context);
        $this->_quoteRepository = $quoteRepository;
        $this->_orderRepository = $orderRepository;
        $this->_filterBuilder = $filterBuilder;
        $this->_filterGroupBuilder = $filterGroupBuilder;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param array $filters
     * @param null $pageSize
     * @param null $currentPage
     * @return \Magento\Framework\Api\SearchCriteria
     * @example
     *          $filters = array(
     *                              array('field' => 'name', 'value' => 'John', 'conditionType' => 'eq'),
     *                              array('field' => 'age', 'value' => '50', 'conditionType' => 'gt')
     *                          )
     */
    public function buildSearchCriteriaWithOR(array $filters, $pageSize = null, $currentPage = null){

        foreach ($filters as $index => $filter){
            $filters[$index] = $this->_filterBuilder
                ->setField($filter['field'])
                ->setValue($filter['value'])
                ->setConditionType($filter['conditionType'])
                ->create();
        }

        //Filters in the same FilterGroup will be search with OR
        $filterGroup = $this->_filterGroupBuilder->setFilters($filters)->create();
        $searchCriteria = $this->_searchCriteriaBuilder->setFilterGroups(array($filterGroup));

        if(isset($pageSize)){
            $searchCriteria->setPageSize($pageSize);
        }

        if(isset($currentPage)) {
            $searchCriteria->setCurrentPage($currentPage);
        }

        return $searchCriteria->create();
    }
}
