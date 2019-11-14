<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 2019-11-14
 * Time: 16:29
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Checkout\Model\Session;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Quote\Model\QuoteFactory;
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

    public function execute()
    {
        /** Reload quote and cancel order if it was pre-saved but not completed */
        $presavedOrderId = $this->checkoutSession->getData(
            \Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY
        );

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
    }
}
