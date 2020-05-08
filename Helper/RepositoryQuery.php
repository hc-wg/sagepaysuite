<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Helper;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

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
    ) {
        parent::__construct($context);
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param array $filters
     * @param null $pageSize
     * @param null $currentPage
     * @return SearchCriteria
     * @example
     *          $filters = array(
     *                              array('field' => 'name', 'value' => 'John', 'conditionType' => 'eq'),
     *                              array('field' => 'age', 'value' => '50', 'conditionType' => 'gt')
     *                          )
     */
    public function buildSearchCriteriaWithOR(array $filters, $pageSize = null, $currentPage = null)
    {
        foreach ($filters as $index => $filter) {
            $filters[$index] = $this->filterBuilder
                ->setField($filter['field'])
                ->setValue($filter['value'])
                ->setConditionType($filter['conditionType'])
                ->create();
        }

        //Filters in the same FilterGroup will be search with OR
        $filterGroup = $this->filterGroupBuilder->setFilters($filters)->create();
        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([$filterGroup]);

        $searchCriteria = $this->setPageSizeAndCurrentPage($searchCriteria, $pageSize, $currentPage);

        return $searchCriteria->create();
    }

    /**
     * @param array $filter1
     * @param array $filter2
     * @param null $pageSize
     * @param null $currentPage
     * @return SearchCriteria
     */
    public function buildSearchCriteriaWithAND(array $filters, $pageSize = null, $currentPage = null)
    {
        $filterGroups = [];
        foreach ($filters as $index => $filter) {
            $filterBuilt = $this->filterBuilder
                ->setField($filter['field'])
                ->setValue($filter['value'])
                ->setConditionType($filter['conditionType'])
                ->create();
            $filterGroups[] = $this->filterGroupBuilder->setFilters([$filterBuilt])->create();
        }

        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups($filterGroups);

        $searchCriteria = $this->setPageSizeAndCurrentPage($searchCriteria, $pageSize, $currentPage);

        return $searchCriteria->create();
    }

    /**
     * @param $pageSize
     * @param $currentPage
     * @param $searchCriteria
     * @return SearchCriteria $searchCriteria
     */
    private function setPageSizeAndCurrentPage($searchCriteria, $pageSize, $currentPage)
    {
        if (isset($pageSize)) {
            $searchCriteria->setPageSize($pageSize);
        }

        if (isset($currentPage)) {
            $searchCriteria->setCurrentPage($currentPage);
        }

        return $searchCriteria;
    }
}
