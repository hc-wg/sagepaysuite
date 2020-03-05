<?php

namespace Ebizmarts\SagePaySuite\Plugin;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\Filter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;

class OrderIncrementIdChecker
{
    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var FilterBuilder */
    private $filterBuilder;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /**
     * OrderIncrementIdChecker constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct
    (
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->orderRepository       = $orderRepository;
        $this->storeManager          = $storeManager;
        $this->filterBuilder         = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function afterIsIncrementIdUsed($subject, $result, $orderIncrementId)
    {
        if ($result) {
            $searchCriteria = $this->createSearchCriteria($orderIncrementId);
            $ordersList = $this->orderRepository->getList($searchCriteria)->getItems();
            if (empty($ordersList)) {
                return false;
            }
        }
        return $result;
    }

    private function createSearchCriteria($orderIncrementId)
    {
        $this->createFiltersAndAddToSearchCriteriaBuilder($orderIncrementId);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $searchCriteria;
    }

    private function createFiltersAndAddToSearchCriteriaBuilder($orderIncrementId)
    {
        $filter1 = $this->filterBuilder
            ->setField('increment_id')
            ->setConditionType('eq')
            ->setValue($orderIncrementId)
            ->create();
        $this->searchCriteriaBuilder->addFilter($filter1);

        $filter2 = $this->filterBuilder
            ->setField('store_id')
            ->setConditionType('eq')
            ->setValue($this->getStoreId())
            ->create();
        $this->searchCriteriaBuilder->addFilter($filter2);
    }

    private function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
