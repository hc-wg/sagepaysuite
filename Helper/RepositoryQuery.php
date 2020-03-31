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
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;


    /**
     * RepositoryQuery constructor.
     * @param Context $context
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        parent::__construct($context);
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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

        foreach ($filters as $index => $filter) {
            $filters[$index] = $this->filterBuilder
                ->setField($filter['field'])
                ->setValue($filter['value'])
                ->setConditionType($filter['conditionType'])
                ->create();
        }

        //Filters in the same FilterGroup will be search with OR
        $filterGroup = $this->filterGroupBuilder->setFilters($filters)->create();
        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups(array($filterGroup));

        if (isset($pageSize)) {
            $searchCriteria->setPageSize($pageSize);
        }

        if (isset($currentPage)) {
            $searchCriteria->setCurrentPage($currentPage);
        }

        return $searchCriteria->create();
    }
}
