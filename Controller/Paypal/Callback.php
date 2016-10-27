<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Paypal;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Callback extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    private $_transactionFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    private $_checkoutHelper;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    private $_postData;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $_orderFactory;

    /** @var \Ebizmarts\SagePaySuite\Model\Api\Post */
    private $_postApi;

    /** @var \Magento\Sales\Model\Order */
    private $_order;

    /** @var \Magento\Sales\Model\Order\Email\Sender\OrderSender */
    private $orderSender;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $_quoteFactory;

    /**
     * Callback constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     * @param \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper
     * @param \Ebizmarts\SagePaySuite\Model\Api\Post $postApi
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        Logger $suiteLogger,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Model\Api\Post $postApi,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
    
        parent::__construct($context);
        $this->_config             = $config;
        $this->_logger             = $logger;
        $this->_transactionFactory = $transactionFactory;
        $this->_checkoutSession    = $checkoutSession;
        $this->_checkoutHelper     = $checkoutHelper;
        $this->_suiteLogger        = $suiteLogger;
        $this->_postApi            = $postApi;
        $this->_quote              = $quote;
        $this->_orderFactory       = $orderFactory;
        $this->orderSender         = $orderSender;
        $this->_quoteFactory       = $quoteFactory;

        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);
    }

    /**
     * Paypal callback
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            //get POST data
            $this->_postData = $this->getRequest()->getPost();

            //log response
            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $this->_postData);

            if (empty($this->_postData) || !isset($this->_postData->Status) || $this->_postData->Status != "PAYPALOK") {
                if (!empty($this->_postData) && isset($this->_postData->StatusDetail)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __("Can not place PayPal order: " . $this->_postData->StatusDetail)
                    );
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __("Can not place PayPal order, please try another payment method")
                    );
                }
            }

            $this->_quote = $this->_quoteFactory->create()->load($this->getRequest()->getParam("quoteid"));
            if (empty($this->_quote->getId())) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Unable to find payment data.")
                );
            }

            $order = $this->_order = $this->_orderFactory->create()->loadByIncrementId(
                $this->_quote->getReservedOrderId()
            );
            if ($order === null || $order->getId() === null) {
                throw new \Magento\Framework\Exception\LocalizedException(__("Invalid order."));
            }

            //send COMPLETION post to sagepay
            $completionResponse = $this->_sendCompletionPost()["data"];

            /**
             *  SUCCESSFULLY COMPLETED PAYMENT (CAPTURE, DEFER or AUTH)
             */

            $transactionId = $completionResponse["VPSTxId"];
            $transactionId = str_replace("{", "", str_replace("}", "", $transactionId)); //strip brackets

            $payment = $order->getPayment();

            //update payment details
            if (!empty($transactionId) && $payment->getLastTransId() == $transactionId) { //validate transaction id
                $payment->setAdditionalInformation('statusDetail', $completionResponse['StatusDetail']);
                $payment->setAdditionalInformation('threeDStatus', $completionResponse['3DSecureStatus']);
                $payment->setCcType("PayPal");
                $payment->setLastTransId($transactionId);
                $payment->save();
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Invalid transaction id'));
            }

            $this->_confirmPayment($transactionId);

            //prepare session to success or cancellation page
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastSuccessQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastOrderId($order->getId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($order->getStatus());
            $this->_checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", null);

            $this->_redirect('checkout/onepage/success');

            return;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->_redirectToCartAndShowError('We can\'t place the order: ' . $e->getMessage());
        }
    }

    private function _sendCompletionPost()
    {
        $request = [
            "VPSProtocol" => $this->_config->getVPSProtocol(),
            "TxType"      => "COMPLETE",
            "VPSTxId"     => $this->_postData->VPSTxId,
            "Amount"      => number_format($this->_quote->getGrandTotal(), 2, '.', ''),
            "Accept"      => "YES"
        ];

        return $this->_postApi->sendPost(
            $request,
            $this->_getServiceURL(),
            ["OK", 'REGISTERED', 'AUTHENTICATED'],
            'Invalid response from PayPal'
        );
    }

    /**
     * Redirect customer to shopping cart and show error message
     *
     * @param string $errorMessage
     * @return void
     */
    private function _redirectToCartAndShowError($errorMessage)
    {
        $this->messageManager->addError($errorMessage);
        $this->_redirect('checkout/cart');
    }

    private function _getServiceURL()
    {
        if ($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PAYPAL_COMPLETION_LIVE;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PAYPAL_COMPLETION_TEST;
        }
    }

    /**
     * @return array
     */
    private function getActionClosedForPaymentAction()
    {
        switch ($this->_config->getSagepayPaymentAction()) {
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT:
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                $closed = true;
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER:
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                $closed = false;
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE:
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                $closed = false;
                break;
            default:
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                $closed = true;
                break;
        }
        return [$action, $closed];
    }

    private function _confirmPayment($transactionId)
    {
        //invoice
        $payment = $this->_order->getPayment();
        $payment->getMethodInstance()->markAsInitialized();
        $this->_order->place()->save();

        $this->orderSender->send($this->_order);

        list($action, $closed) = $this->getActionClosedForPaymentAction();
        $transaction = $this->_transactionFactory->create();
        $transaction->setOrderPaymentObject($payment);
        $transaction->setTxnId($transactionId);
        $transaction->setOrderId($this->_order->getEntityId());
        $transaction->setTxnType($action);
        $transaction->setPaymentId($payment->getId());
        $transaction->setIsClosed($closed);
        $transaction->save();

        //update invoice transaction id
        $this->_order->getInvoiceCollection()
            ->setDataToAll('transaction_id', $payment->getLastTransId())
            ->save();
    }
}
