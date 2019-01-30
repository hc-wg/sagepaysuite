<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Controller\Server;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class Cancel extends Action
{
    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $suiteLogger;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Quote
     */
    private $quote;

    /** @var QuoteIdMaskFactory */
    private $quoteIdMaskFactory;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Cancel constructor.
     * @param Context $context
     * @param Logger $suiteLogger
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param Quote $quote
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context $context,
        Logger $suiteLogger,
        Config $config,
        LoggerInterface $logger,
        Session $checkoutSession,
        Quote $quote,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        OrderFactory $orderFactory,
        EncryptorInterface $encryptor
    ) {
    
        parent::__construct($context);
        $this->suiteLogger = $suiteLogger;
        $this->config      = $config;
        $this->config->setMethodCode(Config::METHOD_SERVER);
        $this->logger             = $logger;
        $this->checkoutSession    = $checkoutSession;
        $this->quote              = $quote;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->orderFactory       = $orderFactory;
        $this->encryptor          = $encryptor;
    }

    public function execute()
    {
        $this->saveErrorMessage();

        $storeId = $this->getRequest()->getParam("_store");
        $quoteId = $this->encryptor->decrypt($this->getRequest()->getParam("quote"));

        $this->quote->setStoreId($storeId);
        $this->quote->load($quoteId);

        if (empty($this->quote->getId())) {
            throw new \Exception("Quote not found.");
        }

        $order = $this->orderFactory->create()->loadByIncrementId($this->quote->getReservedOrderId());
        if (empty($order->getId())) {
            throw new \Exception("Order not found.");
        }

        $this->recoverCart($order);

        $this->inactivateQuote($this->quote);

        $this
            ->getResponse()
            ->setBody(
                '<script>window.top.location.href = "'
                . $this->_url->getUrl('checkout/cart', [
                    '_secure' => true,
                ])
                . '";</script>'
            );
    }

    private function saveErrorMessage()
    {
        $message = $this->getRequest()->getParam("message");
        if (!empty($message)) {
            $this->messageManager->addError($message);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    private function recoverCart($order)
    {
        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $this->_objectManager->get("Magento\Checkout\Model\Cart");
        $cart->setQuote($this->checkoutSession->getQuote());
        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            try {
                $cart->addOrderItem($item);
            } catch (\Exception $e) {
                $this->suiteLogger->logException($e, [__METHOD__, __LINE__]);
            }
        }
        $cart->save();
    }

    /**
     * @param Quote $quote
     */
    private function inactivateQuote($quote)
    {
        $quote->setIsActive(0);
        $quote->save();
    }
}
