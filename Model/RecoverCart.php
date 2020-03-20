<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Message\ManagerInterface;

class RecoverCart
{
    const ORDER_ERROR_MESSAGE   = "Order not availabe";
    const QUOTE_ERROR_MESSAGE   = "Quote not availabe";
    const GENERAL_ERROR_MESSAGE = "Not possible to recover quote";

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var Logger */
    private $suiteLogger;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var QuoteFactory */
    private $quoteFactory;

    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var bool */
    private $_shouldCancelOrder;

    /**
     * RecoverCart constructor.
     * @param CheckoutSession $checkoutSession
     * @param Logger $suiteLogger
     * @param OrderRepositoryInterface $orderRepository
     * @param QuoteFactory $quoteFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param DataObjectFactory $dataObjectFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Logger $suiteLogger,
        OrderRepositoryInterface $orderRepository,
        QuoteFactory $quoteFactory,
        CartRepositoryInterface $quoteRepository,
        DataObjectFactory $dataObjectFactory,
        ManagerInterface $messageManager
    ) {
        $this->checkoutSession   = $checkoutSession;
        $this->suiteLogger       = $suiteLogger;
        $this->orderRepository   = $orderRepository;
        $this->quoteFactory      = $quoteFactory;
        $this->quoteRepository   = $quoteRepository;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->messageManager    = $messageManager;
    }


    public function execute()
    {
        $order = $this->getOrder();

        if ($this->verifyIfOrderIsValid($order)) {
            $quote = $this->checkoutSession->getQuote();
            if (!empty($quote)) {
                if ($this->_shouldCancelOrder) {
                    $order->cancel()->save();
                }
                try {
                    $this->cloneQuoteAndReplaceInSession($order);
                } catch (LocalizedException $e) {
                    $this->logExceptionAndShowError(self::GENERAL_ERROR_MESSAGE, $e);
                } catch (NoSuchEntityException $e) {
                    $this->logExceptionAndShowError(self::GENERAL_ERROR_MESSAGE, $e);
                }
                $this->removeFlag();
            } else {
                $this->addError(self::QUOTE_ERROR_MESSAGE);
            }
        } else {
            $this->addError(self::ORDER_ERROR_MESSAGE);
        }
    }

    /**
     * @param Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function cloneQuoteAndReplaceInSession(Order $order)
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

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    private function getOrder()
    {
        /** Get order if it was pre-saved but not completed */
        $presavedOrderId = $this->checkoutSession->getData(SagePaySession::PRESAVED_PENDING_ORDER_KEY);

        if (!empty($presavedOrderId)) {
            $order = $this->orderRepository->get($presavedOrderId);
        } else {
            $order = null;
        }

        return $order;
    }

    /**
     * @param $order
     * @return bool
     */
    private function verifyIfOrderIsValid($order)
    {
        return $order !== null &&
            $order->getId() !== null &&
            $order->getState() === Order::STATE_PENDING_PAYMENT;
    }

    private function removeFlag()
    {
        $this->checkoutSession->setData(SagePaySession::PRESAVED_PENDING_ORDER_KEY, null);
        $this->checkoutSession->setData(SagePaySession::CONVERTING_QUOTE_TO_ORDER, 0);
    }

    /**
     * @param $shouldCancelOrder
     * @return $this
     */
    public function setShouldCancelOrder($shouldCancelOrder)
    {
        $this->_shouldCancelOrder = $shouldCancelOrder;
        return $this;
    }

    /**
     * @param $message
     */
    private function addError($message)
    {
        $this->removeFlag();
        $this->messageManager->addError(__($message));
    }

    /**
     * @param $message
     * @param $exception
     */
    private function logExceptionAndShowError($message, $exception)
    {
        $this->addError($message);
        $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $exception->getTraceAsString(), [__METHOD__, __LINE__]);
    }
}
