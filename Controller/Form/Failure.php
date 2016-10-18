<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Failure extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form
     */
    private $_formModel;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /** @var \Magento\Sales\Model\Order */
    private $_order;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $_quoteFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param Logger $suiteLogger
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Ebizmarts\SagePaySuite\Model\Form $formModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Psr\Log\LoggerInterface $logger,
        \Ebizmarts\SagePaySuite\Model\Form $formModel,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
    
        parent::__construct($context);
        $this->_suiteLogger     = $suiteLogger;
        $this->_logger          = $logger;
        $this->_formModel       = $formModel;
        $this->_orderFactory    = $orderFactory;
        $this->_quoteFactory    = $quoteFactory;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            //decode response
            $response = $this->_formModel->decodeSagePayResponse($this->getRequest()->getParam("crypt"));

            //log response
            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $response);

            if (!array_key_exists("Status", $response) || !array_key_exists("StatusDetail", $response)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid response from Sage Pay'));
            }

            $this->_quote = $this->_quoteFactory->create()->load($this->getRequest()->getParam("quoteid"));
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($this->_quote->getReservedOrderId());

            //cancel pending payment order
            $this->_cancelOrder();

            $statusDetail = $response["StatusDetail"];
            $statusDetail = explode(" : ", $statusDetail);
            $statusDetail = $statusDetail[1];

            $this->_checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", null);

            $this->messageManager->addError($response["Status"] . ": " . $statusDetail);
            return $this->_redirect('checkout/cart');
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_logger->critical($e);
        }
    }

    private function _cancelOrder()
    {
        try {
            $this->_order->cancel()->save();

            //recover quote
            if ($this->_quote->getId()) {
                $this->_quote->setIsActive(1);
                $this->_quote->setReservedOrderId(null);
                $this->_quote->save();

                $this->_checkoutSession->replaceQuote($this->_quote);
            }

            //Unset data
            $this->_checkoutSession->unsLastRealOrderId();
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e);
        }
    }
}
