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
    protected $_config;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    protected $_checkoutHelper;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    protected $_postData;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post
     */
    protected $_postApi;

    /**
     * Success constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param OrderSender $orderSender
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        Logger $suiteLogger,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Model\Api\Post $postApi
    ) {
    
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);
        $this->_logger = $logger;
        $this->_transactionFactory = $transactionFactory;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_postApi = $postApi;
        $this->_quote = $this->_checkoutSession->getQuote();
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
            $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $this->_postData);

            if (empty($this->_postData) || !isset($this->_postData->Status) || $this->_postData->Status != "PAYPALOK") {
                if (!empty($this->_postData) && isset($this->_postData->StatusDetail)) {
                    throw new \Magento\Framework\Exception\LocalizedException(__("Can not place PayPal order: " . $this->_postData->StatusDetail));
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(__("Can not place PayPal order, please try another payment method"));
                }
            }

            //send COMPLETION post to sagepay
            $completion_response = $this->_sendCompletionPost()["data"];

            /**
             *  SUCCESSFULLY COMPLETED PAYMENT (CAPTURE, DEFER or AUTH)
             */

            $transactionId = $completion_response["VPSTxId"];
            $transactionId = str_replace("{", "", str_replace("}", "", $transactionId)); //strip brackets

            //import payment info for save order
            $payment = $this->_quote->getPayment();
            $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setCcType("PayPal");
            $payment->setAdditionalInformation('statusDetail', $completion_response["StatusDetail"]);
            $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
            $payment->setAdditionalInformation('mode', $this->_config->getMode());
            $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());

            $order = $this->_checkoutHelper->placeOrder();
            $quoteId = $this->_quote->getId();

            //prepare session to success or cancellation page
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($quoteId);
            $this->_checkoutSession->setLastSuccessQuoteId($quoteId);

            //an order may be created
            if ($order) {
                $this->_checkoutSession->setLastOrderId($order->getId());
                $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->_checkoutSession->setLastOrderStatus($order->getStatus());

                //send email
                $this->_checkoutHelper->sendOrderEmail($order);
            }

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setIsTransactionClosed(1);
            $payment->save();

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

            //create transaction record
            $transaction = $this->_transactionFactory->create();
            $transaction->setOrderPaymentObject($payment);
            $transaction->setTxnId($transactionId);
            $transaction->setOrderId($order->getEntityId());
            $transaction->setTxnType($action);
            $transaction->setPaymentId($payment->getId());
            $transaction->setIsClosed($closed);
            $transaction->save();

            //update invoice transaction id
            $invoices = $order->getInvoiceCollection();
            if (!empty($invoices)) {
                foreach ($invoices as $_invoice) {
                    $_invoice->setTransactionId($payment->getLastTransId());
                    $_invoice->save();
                }
            }

            $this->_redirect('checkout/onepage/success');

            return;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->_redirectToCartAndShowError('We can\'t place the order: ' . $e->getMessage());
        }
    }

    protected function _sendCompletionPost()
    {
        $request = [
            "VPSProtocol" => $this->_config->getVPSProtocol(),
            "TxType" => "COMPLETE",
            "VPSTxId" => $this->_postData->VPSTxId,
            "Amount" => number_format($this->_quote->getGrandTotal(), 2, '.', ''),
            "Accept" => "YES"
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
    protected function _redirectToCartAndShowError($errorMessage)
    {
        $this->messageManager->addError($errorMessage);
        $this->_redirect('checkout/cart');
    }

    protected function _getServiceURL()
    {
        if ($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PAYPAL_COMPLETION_LIVE;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PAYPAL_COMPLETION_TEST;
        }
    }
}
