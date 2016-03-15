<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Observer;

use Magento\Framework\Event\ObserverInterface;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class CheckoutCartIndex implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        Logger $suiteLogger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_suiteLogger = $suiteLogger;
        $this->_orderFactory = $orderFactory;
        $this->_quoteFactory = $quoteFactory;
    }

    /**
     * Checkout Cart index observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * Reload quote and cancel order if it was pre-saved but not completed
         */
        $presavedOrderId = $this->_checkoutSession->getData("sagepaysuite_presaved_order_pending_payment");

        if (!empty($presavedOrderId))
        {
            $order = $this->_orderFactory->create()->load($presavedOrderId);
            if (!is_null($order) && !is_null($order->getId()) && $order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
            {
                $quote = $this->_checkoutSession->getQuote();
                if (empty($quote) || empty($quote->getId()))
                {
                    //cancel order
                    $order->cancel()->save();

                    //recover quote
                    $quote = $this->_quoteFactory->create()->load($order->getQuoteId());
                    if ($quote->getId())
                    {
                        $quote->setIsActive(1);
                        $quote->setReservedOrderId(NULL);
                        $quote->save();
                        $this->_checkoutSession->replaceQuote($quote);
                    }

                    //remove flag
                    $this->_checkoutSession->setData("sagepaysuite_presaved_order_pending_payment",null);
                }
            }
        }
    }
}