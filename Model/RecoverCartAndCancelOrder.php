<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Magento\Checkout\Model\Session;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\DataObjectFactory;

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

    /** @var QuoteRepository */
    private $quoteRepository;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    /**
     * RecoverCartAndCancelOrder constructor.
     * @param Session $checkoutSession
     * @param Logger $suiteLogger
     * @param OrderFactory $orderFactory
     * @param QuoteFactory $quoteFactory
     * @param QuoteRepository $quoteRepository
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        Session $checkoutSession,
        Logger $suiteLogger,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        QuoteRepository $quoteRepository,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->checkoutSession   = $checkoutSession;
        $this->suiteLogger       = $suiteLogger;
        $this->orderFactory      = $orderFactory;
        $this->quoteFactory      = $quoteFactory;
        $this->quoteRepository   = $quoteRepository;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    public function execute(bool $cancelOrder)
    {
        $order = $this->getOrder();

        if ($this->verifyIfOrderIsValid($order)) {
            $quote = $this->checkoutSession->getQuote();
            if (!empty($quote)) {
                if ($cancelOrder) {
                    $order->cancel()->save();
                }
                $this->cloneQuoteAndReplaceInSession($order);
                $this->removeFlag();
            }
        }
    }

    private function cloneQuoteAndReplaceInSession($order)
    {
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $items = $quote->getAllVisibleItems();

        $newQuote = $this->quoteFactory->create();
        $newQuote->setStoreId($quote->getStoreId());
        $newQuote->setIsActive(1);
        $newQuote->setReservedOrderId(null);
        foreach ($items as $item) {
            $product = $item->getProduct();

            $options = $product->getTypeInstance(true)->getOrderOptions($product);

            $info = $options['info_buyRequest'];
            $request = $this->dataObjectFactory->create();
            $request->setData($info);

            $newQuote->addProduct($product, $request);
        }
        $newQuote->collectTotals();
        $newQuote->save();

        $this->checkoutSession->replaceQuote($newQuote);
    }

    private function getOrder()
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

    private function verifyIfOrderIsValid($order)
    {
        return $order !== null &&
            $order->getId() !== null &&
            $order->getState() === Order::STATE_PENDING_PAYMENT;
    }

    private function removeFlag()
    {
        $this->checkoutSession->setData(SagePaySession::PRESAVED_PENDING_ORDER_KEY, null);
        $this->checkoutSession->setData(SagePaySession::QUOTE_IS_ACTIVE, 1);
    }
}
