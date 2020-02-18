<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Server;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\OrderLoader;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
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
     * @var OrderFactory
     */
    private $_orderFactory;

    /**
     * @var QuoteFactory
     */
    private $_quoteFactory;

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
     * @param OrderFactory $orderFactory
     * @param QuoteFactory $quoteFactory
     * @param EncryptorInterface $encryptor
     * @param OrderLoader $orderLoader
     */
    public function __construct(
        Context $context,
        Logger $suiteLogger,
        LoggerInterface $logger,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        EncryptorInterface $encryptor,
        OrderLoader $orderLoader
    ) {
    
        parent::__construct($context);

        $this->_suiteLogger     = $suiteLogger;
        $this->_logger          = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory    = $orderFactory;
        $this->_quoteFactory    = $quoteFactory;
        $this->encryptor        = $encryptor;
        $this->orderLoader      = $orderLoader;
    }

    public function execute()
    {
        try {
            $storeId = $this->getRequest()->getParam("_store");
            $quoteId = $this->encryptor->decrypt($this->getRequest()->getParam("quoteid"));

            $quote = $this->_quoteFactory->create();
            $quote->setStoreId($storeId);
            $quote->load($quoteId);

            $order = $this->orderLoader->loadOrderFromQuote($quote);

            //prepare session to success page
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($quote->getId());
            $this->_checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->_checkoutSession->setLastOrderId($order->getId());
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
