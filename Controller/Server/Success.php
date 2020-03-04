<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Server;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

class Success extends Action
{

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var OrderRepository
     */
    private $_orderRepository;

    /**
     * @var QuoteRepository
     */
    private $_quoteRepository;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var FilterBuilder
     */
    private $_filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $_filterGroupBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $_searchCriteriaBuilder;

    /**
     * Success constructor.
     * @param Context $context
     * @param Logger $suiteLogger
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param OrderRepository $orderRepository
     * @param QuoteRepository $quoteRepository
     * @param EncryptorInterface $encryptor
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        Logger $suiteLogger,
        LoggerInterface $logger,
        Session $checkoutSession,
        OrderRepository $orderRepository,
        QuoteRepository $quoteRepository,
        EncryptorInterface $encryptor,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {

        parent::__construct($context);

        $this->_suiteLogger     = $suiteLogger;
        $this->_logger          = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderRepository    = $orderRepository;
        $this->_quoteRepository    = $quoteRepository;
        $this->encryptor        = $encryptor;
        $this->_filterBuilder        = $filterBuilder;
        $this->_filterGroupBuilder        = $filterGroupBuilder;
        $this->_searchCriteriaBuilder        = $searchCriteriaBuilder;
    }

    public function execute()
    {
        try {
            $storeId = $this->getRequest()->getParam("_store");
            $quoteId = $this->encryptor->decrypt($this->getRequest()->getParam("quoteid"));
            $quote = $this->_quoteRepository->get($quoteId, array($storeId));

            $incrementIdFilter = $this->_filterBuilder
                ->setField('increment_id')
                ->setConditionType('eq')
                ->setValue($quote->getReservedOrderId())
                ->create();

            $filterGroup = $this->_filterGroupBuilder
                ->setFilters(array($incrementIdFilter))
                ->create();

            $searchCriteria = $this->_searchCriteriaBuilder
                ->setFilterGroups(array($filterGroup))
                ->setPageSize(1)
                ->setCurrentPage(1)
                ->create();

            /**
             * @var Order
             */
            $order = null;
            $orders = $this->_orderRepository->getList($searchCriteria);
            $ordersCount = $orders->getTotalCount();
            $orders = $orders->getItems();

            if ($ordersCount > 0) {
                $order = current($orders);
            }

            //prepare session to success page
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($quote->getId());
            $this->_checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->_checkoutSession->setLastOrderId($order->getEntityId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($order->getStatus());

            //remove order pre-saved flag from checkout
            $this->_checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->messageManager->addError(__('An error ocurred.'));
        }

        //redirect to success via javascript
        $this->getResponse()->setBody(
            '<script>window.top.location.href = "'
            . $this->_url->getUrl('checkout/onepage/success', ['_secure' => true])
            . '";</script>'
        );
    }
}
