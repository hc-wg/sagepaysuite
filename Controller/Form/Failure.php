<?php
declare(strict_types=1);
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Ebizmarts\SagePaySuite\Model\Form;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Failure extends Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $suiteLogger;

    /**
     * @var Form
     */
    private $formModel;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /** @var \Magento\Sales\Model\Order */
    private $order;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $quote;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Context $context
     * @param Logger $suiteLogger
     * @param LoggerInterface $logger
     * @param Form $formModel
     */
    public function __construct(
        Context $context,
        Logger $suiteLogger,
        LoggerInterface $logger,
        Form $formModel,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        Session $checkoutSession,
        EncryptorInterface $encryptor
    ) {
    
        parent::__construct($context);
        $this->suiteLogger     = $suiteLogger;
        $this->logger          = $logger;
        $this->formModel       = $formModel;
        $this->orderFactory    = $orderFactory;
        $this->quoteFactory    = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->encryptor       = $encryptor;
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            //decode response
            $response = $this->formModel->decodeSagePayResponse($this->getRequest()->getParam("crypt"));

            //log response
            $this->suiteLogger->sageLog(Logger::LOG_REQUEST, $response, [__METHOD__, __LINE__]);

            if (!array_key_exists("Status", $response) || !array_key_exists("StatusDetail", $response)) {
                throw new LocalizedException(__('Invalid response from Sage Pay'));
            }

            $this->quote = $this->quoteFactory->create()->load(
                $this->encryptor->decrypt($this->getRequest()->getParam("quoteid"))
            );
            $this->order = $this->orderFactory->create()->loadByIncrementId($this->quote->getReservedOrderId());

            //cancel pending payment order
            $this->cancelOrder();

            $statusDetail = $this->extractStatusDetail($response);

            $this->checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", null);

            $this->messageManager->addError($response["Status"] . ": " . $statusDetail);
            return $this->_redirect('checkout/cart');
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->logger->critical($e);
        }
    }

    private function cancelOrder()
    {
        try {
            $this->order->cancel()->save();

            //recover quote
            if ($this->quote->getId()) {
                $this->quote->setIsActive(1);
                $this->quote->setReservedOrderId(null);
                $this->quote->save();

                $this->checkoutSession->replaceQuote($this->quote);
            }

            //Unset data
            $this->checkoutSession->unsLastRealOrderId();
        } catch (\Exception $e) {
            $this->suiteLogger->logException($e, [__METHOD__, __LINE__]);
        }
    }

    /**
     * @param array $response
     * @return string
     */
    private function extractStatusDetail(array $response): string
    {
        $statusDetail = $response["StatusDetail"];

        if (strpos($statusDetail, ':') !== false) {
            $statusDetail = explode(" : ", $statusDetail);
            $statusDetail = $statusDetail[1];
        }


        return $statusDetail;
    }
}
