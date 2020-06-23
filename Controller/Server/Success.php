<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Server;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class Success extends \Magento\Framework\App\Action\Action
{

    /**
     * Logging instance
     * @var Logger
     */
    private $_suiteLogger;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var CheckoutSession
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
     * @param Context $context
     * @param Logger $suiteLogger
     * @param LoggerInterface $logger
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param QuoteFactory $quoteFactory
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        Logger $suiteLogger,
        LoggerInterface $logger,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);

        $this->_suiteLogger     = $suiteLogger;
        $this->_logger          = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory    = $orderFactory;
        $this->_quoteFactory    = $quoteFactory;
        $this->encryptor        = $encryptor;
    }

    public function execute()
    {
        try {
            $request = $this->getRequest();

            $storeId = $request->getParam("_store");
            $quoteId = $this->encryptor->decrypt($request->getParam("quoteid"));

            $quote = $this->_quoteFactory->create();
            $quote->setStoreId($storeId);
            $quote->load($quoteId);

            $order = $this->_orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());

            //prepare session to success page
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($quote->getId());
            $this->_checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->_checkoutSession->setLastOrderId($order->getId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($order->getStatus());

            //remove order pre-saved flag from checkout
            $this->_checkoutSession->setData(SagePaySession::PRESAVED_PENDING_ORDER_KEY, null);
            $this->_checkoutSession->setData(SagePaySession::CONVERTING_QUOTE_TO_ORDER, 0);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->messageManager->addError(__('An error ocurred.'));
        }

        return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success', ['_secure' => true]);
    }
}
