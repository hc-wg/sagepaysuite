<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Magento\Checkout\Model\Session;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class RecoverCartAndCancelOrder
{
    /** @var Session */
    private $checkoutSession;

    /** @var Logger */
    private $suiteLogger;

    /** @var OrderFactory */
    private $orderFactory;

    /** @var QuoteFactory */
    private $quoteFactory;

    /**
     * RecoverCartAndCancelOrder constructor.
     * @param Session $checkoutSession
     * @param Logger $suiteLogger
     * @param OrderFactory $orderFactory
     * @param QuoteFactory $quoteFactory
     */
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

    public function execute(bool $cancelOrder)
    {
        $order = $this->getOrder();

        if ($this->verifyIfOrderIsValid($order)) {
            $quote = $this->checkoutSession->getQuote();
            if (empty($quote) || empty($quote->getId())) {
                if ($cancelOrder) {
                    $order->cancel()->save();
                }
                $this->recoverQuote($order);
                $this->removeFlag();
            }
        }
    }

    public function recoverQuote($order)
    {
        $quote = $this->quoteFactory->create()->load($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(1);
            $quote->setReservedOrderId(null);
            $quote->save();
            $this->checkoutSession->replaceQuote($quote);
        }
    }

    public function getOrder()
    {
        /** Get order if it was pre-saved but not completed */
        $presavedOrderId = $this->checkoutSession->getData(SagePaySession::PRESAVED_PENDING_ORDER_KEY);

        if (!empty($presavedOrderId)) {
            $order = $this->orderFactory->create()->load($presavedOrderId);
        } else {
            $order = null;
        }

        return $order;
    }

    public function verifyIfOrderIsValid($order)
    {
        return $order !== null &&
            $order->getId() !== null &&
            $order->getState() === Order::STATE_PENDING_PAYMENT;
    }

    public function removeFlag()
    {
        $this->checkoutSession->setData(SagePaySession::PRESAVED_PENDING_ORDER_KEY, null);
        $this->checkoutSession->setData(SagePaySession::QUOTE_IS_ACTIVE, 1);
    }
}
