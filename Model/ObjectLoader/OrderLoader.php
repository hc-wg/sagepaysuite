<?php

namespace Ebizmarts\SagePaySuite\Model\ObjectLoader;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Ebizmarts\SagePaySuite\Helper\RepositoryQuery;

class OrderLoader
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var RepositoryQuery */
    private $repositoryQuery;

    /**
     * OrderLoader constructor.
     * @param OrderRepository $orderRepository
     * @param RepositoryQuery $repositoryQuery
     */
    public function __construct(OrderRepository $orderRepository, RepositoryQuery $repositoryQuery)
    {
        $this->orderRepository = $orderRepository;
        $this->repositoryQuery = $repositoryQuery;
    }

    /**
     * @param Quote $quote
     * @return \Magento\Sales\Model\Order
     * @throws LocalizedException
     */
    public function loadOrderFromQuote(Quote $quote)
    {
        $incrementId = $quote->getReservedOrderId();

        $incrementIdFilter = array(
            'field' => 'increment_id',
            'conditionType' => 'eq',
            'value' => $incrementId
        );

        $searchCriteria = $this->repositoryQuery->buildSearchCriteriaWithOR(array($incrementIdFilter));

        /** @var Order */
        $order = null;
        $orders = $this->orderRepository->getList($searchCriteria);
        $ordersCount = $orders->getTotalCount();

        if ($ordersCount > 0) {
            $orders = $orders->getItems();
            $order = current($orders);
        }

        if ($order === null || $order->getId() === null) {
            throw new LocalizedException(__("Invalid order."));
        }

        return $order;
    }
}
