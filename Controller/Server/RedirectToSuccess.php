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

class RedirectToSuccess extends \Magento\Framework\App\Action\Action
{

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $suiteLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

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

        $this->suiteLogger     = $suiteLogger;
        $this->logger          = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->encryptor        = $encryptor;
        $this->orderLoader      = $orderLoader;
    }

    public function execute()
    {
        try {
            $request = $this->getRequest();

            $storeId = $request->getParam("_store");
            $encryptedQuoteId = $request->getParam("quoteid");

        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addError(__('An error ocurred.'));
        }

        //redirect to success via javascript
        $this->getResponse()->setBody(
            '<script>window.top.location.href = "'
            . $this->_url->getUrl('*/*/success', ['_secure' => true, '_store' => $storeId]). '?quoteid=' .  urlencode($encryptedQuoteId)
            . '";</script>'
        );
    }
}
