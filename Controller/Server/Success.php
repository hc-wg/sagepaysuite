<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Server;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\ObjectLoader\OrderLoader;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface;

class Success extends \Magento\Framework\App\Action\Action
{

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var Session
     */
    private $_checkoutSession;

    /**
     * @var QuoteRepository
     */
    private $_quoteRepository;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var OrderLoader
     */
    private $orderLoader;

    /**
     * Success constructor.
     * @param Context $context
     * @param Logger $suiteLogger
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param QuoteRepository $quoteRepository
     * @param EncryptorInterface $encryptor
     * @param OrderLoader $orderLoader
     */
    public function __construct(
        Context $context,
        Logger $suiteLogger,
        LoggerInterface $logger,
        Session $checkoutSession,
        QuoteRepository $quoteRepository,
        EncryptorInterface $encryptor,
        OrderLoader $orderLoader
    ) {

        parent::__construct($context);

        $this->_suiteLogger     = $suiteLogger;
        $this->_logger          = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteRepository = $quoteRepository;
        $this->encryptor        = $encryptor;
        $this->orderLoader      = $orderLoader;
    }

    public function execute()
    {
        try {
            $request = $this->getRequest();

            $storeId = $request->getParam("_store");
            $quoteId = $this->encryptor->decrypt($request->getParam("quoteid"));

            $quote = $this->_quoteRepository->get($quoteId, array($storeId));

            $order = $this->orderLoader->loadOrderFromQuote($quote);

            //prepare session to success page
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($quote->getId());
            $this->_checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->_checkoutSession->setLastOrderId($order->getEntityId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($order->getStatus());

            //remove order pre-saved flag from checkout
            $this->_checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);
            $this->_checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::CONVERTING_QUOTE_TO_ORDER, 0);
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
