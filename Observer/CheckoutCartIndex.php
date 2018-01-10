<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ObserverInterface;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;

class CheckoutCartIndex implements ObserverInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Logger
     */
    private $suiteLogger;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    public function __construct(
        Session $checkoutSession,
        Logger $suiteLogger,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory
    ) {
    
        $this->checkoutSession = $checkoutSession;
        $this->suiteLogger     = $suiteLogger;
        $this->orderFactory    = $orderFactory;
        $this->quoteFactory    = $quoteFactory;
    }

    /**
     * Checkout Cart index observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Magento\Framework\Event\Observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * Reload quote and cancel order if it was pre-saved but not completed
         */
        $presavedOrderId = $this->checkoutSession->getData("sagepaysuite_presaved_order_pending_payment");

        if (!empty($presavedOrderId)) {
            $order = $this->orderFactory->create()->load($presavedOrderId);
            if ($order !== null && $order->getId() !== null
                && $order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
            ) {
                $quote = $this->checkoutSession->getQuote();
                if (empty($quote) || empty($quote->getId())) {
                //cancel order
                    $order->cancel()->save();

                    //recover quote
                    $quote = $this->quoteFactory->create()->load($order->getQuoteId());
                    if ($quote->getId()) {
                        $quote->setIsActive(1);
                        $quote->setReservedOrderId(null);
                        $quote->save();
                        $this->checkoutSession->replaceQuote($quote);
                    }

                    //remove flag
                    $this->checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", null);
                }
            }
        }
        return $observer;
    }
}
