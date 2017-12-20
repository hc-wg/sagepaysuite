<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Controller\Server;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $quote;

    /** @var \Magento\Quote\Model\QuoteIdMaskFactory */
    private $quoteIdMaskFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * Cancel constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param Logger $suiteLogger
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
    
        parent::__construct($context);
        $this->_suiteLogger     = $suiteLogger;
        $this->_config          = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
        $this->_logger          = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->quote            = $quote;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->orderFactory       = $orderFactory;
    }

    public function execute()
    {
        $message = $this->getRequest()->getParam("message");
        if (!empty($message)) {
            $this->messageManager->addError($message);
        }

        $quote = $this->quote->load($this->getRequest()->getParam("quote"));
        $order = $this->orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());
         /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $this->_objectManager->get("Magento\Checkout\Model\Cart");
        $cart->setQuote($this->_checkoutSession->getQuote());
        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            try {
                $cart->addOrderItem($item);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        $cart->save();

        $quote->setIsActive(0);
        $quote->save();

        $this->getResponse()->setBody(
            '<script>window.top.location.href = "'
            . $this->_url->getUrl('checkout/cart', [
                '_secure' => true,
            ])
            . '";</script>'
        );
    }
}
