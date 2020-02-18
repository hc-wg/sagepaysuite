<?php

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Exception\LocalizedException;

class OrderLoader
{
    /** @var OrderFactory */
    private $orderFactory;

    public function __construct(
        OrderFactory $orderFactory
    ){
        $this->orderFactory = $orderFactory;
    }


    /**
     * @param Quote $quote
     * @return \Magento\Sales\Model\Order
     * @throws LocalizedException
     */
    public function loadOrderFromQuote(Quote $quote)
    {
        $incrementId = $quote->getReservedOrderId();
        $storeId = $quote->getStoreId();
        $order = $this->orderFactory->create()->loadByIncrementIdAndStoreId($incrementId, $storeId);
        if ($order === null || $order->getId() === null) {
            throw new LocalizedException(__("Invalid order."));
        }

        return $order;
    }
}
