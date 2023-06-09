<?php
declare(strict_types=1);
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Ebizmarts\SagePaySuite\Model\Form;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

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

    /** @var RecoverCart */
    private $recoverCart;

    /**
     * Failure constructor.
     * @param Context $context
     * @param Logger $suiteLogger
     * @param LoggerInterface $logger
     * @param Form $formModel
     * @param OrderFactory $orderFactory
     * @param QuoteFactory $quoteFactory
     * @param Session $checkoutSession
     * @param EncryptorInterface $encryptor
     * @param RecoverCart $recoverCart
     */
    public function __construct(
        Context $context,
        Logger $suiteLogger,
        LoggerInterface $logger,
        Form $formModel,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        Session $checkoutSession,
        EncryptorInterface $encryptor,
        RecoverCart $recoverCart
    ) {
        parent::__construct($context);
        $this->suiteLogger     = $suiteLogger;
        $this->logger          = $logger;
        $this->formModel       = $formModel;
        $this->orderFactory    = $orderFactory;
        $this->quoteFactory    = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->encryptor       = $encryptor;
        $this->recoverCart     = $recoverCart;
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

            if (!isset($response["Status"]) || !isset($response["StatusDetail"])) {
                throw new LocalizedException(__('Invalid response from Opayo'));
            }

            $orderId = $this->encryptor->decrypt($this->getRequest()->getParam("orderId"));
            $this->suiteLogger->debugLog('OrderId: ' . $orderId, [__METHOD__, __LINE__]);
            $this->recoverCart
                ->setShouldCancelOrder(true)
                ->setOrderId((int)$orderId)
                ->execute();

            $statusDetail = $this->extractStatusDetail($response);

            $this->checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);

            $this->messageManager->addError($response["Status"] . ": " . $statusDetail);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->logger->critical($e);
        }

        $this->addOrderEndLog($response);

        return $this->_redirect('checkout/cart');
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

    /**
     * @param array $response
     * @return string
     */
    private function extractIncrementIdFromVendorTxCode(array $response): string
    {
        $vendorTxCode = explode("-", $response['VendorTxCode']);
        return $vendorTxCode[0];
    }

    /**
     * @param array $response
     */
    private function addOrderEndLog(array $response): void
    {
        $quoteId = $this->encryptor->decrypt($this->getRequest()->getParam("quoteid"));
        $orderId = isset($response['VendorTxCode']) ? $this->extractIncrementIdFromVendorTxCode($response) : "";
        $vpstxid = isset($response['VPSTxId']) ? $response['VPSTxId'] : "";
        $this->suiteLogger->orderEndLog($orderId, $quoteId, $vpstxid);
    }
}
