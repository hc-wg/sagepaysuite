<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Callback3D extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRest
     */
    protected $_pirestapi;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    protected $_checkoutHelper;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    protected $_transactionId;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirest
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirestapi,
        Logger $suiteLogger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
    
        parent::__construct($context);

        $this->_pirestapi = $pirestapi;
        $this->_suiteLogger = $suiteLogger;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_transactionFactory = $transactionFactory;
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_logger = $logger;
        $this->_checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        try {
            //get POST data
            $postData = $this->getRequest()->getPost();

            //submit 3D secure
            $paRes = $postData->PaRes;
            $this->_transactionId = $this->getRequest()->getParam("transactionId");
            $submit3D_result = $this->_pirestapi->submit3D($paRes, $this->_transactionId);

            if (isset($submit3D_result->status) && $submit3D_result->status == "Authenticated") {
                //request transaction details to confirm payment
                $transaction_details_result = $this->_pirestapi->transactionDetails($this->_transactionId);

                //log transaction details response
                $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $transaction_details_result);

                $this->_confirmPayment($transaction_details_result);

                //remove order pre-saved flag from checkout
                $this->_checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", null);

                //redirect to success via javascript
                $this->_javascriptRedirect('checkout/onepage/success');
            } else {
                $this->messageManager->addError("Invalid 3D secure authentication.");
                $this->_javascriptRedirect('checkout/cart');
                //$this->_redirect('checkout/cart');
            }
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            $this->messageManager->addError($apiException->getUserMessage());
            //$this->_redirect('checkout/cart');
            $this->_javascriptRedirect('checkout/cart');
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->messageManager->addError("Something went wrong: " . $e->getMessage());
            //$this->_redirect('checkout/cart');
            $this->_javascriptRedirect('checkout/cart');
        }
    }

    protected function _confirmPayment($response)
    {

        if ($response->statusCode == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {
            $orderId = $this->getRequest()->getParam("orderId");
            $order = $this->_orderFactory->create()->load($orderId);
            $quoteId = $this->getRequest()->getParam("quoteId");

            if (!empty($order)) {
            //set additional payment info
                $payment = $order->getPayment();
                $payment->setAdditionalInformation('statusCode', $response->statusCode);
                $payment->setAdditionalInformation('statusDetail', $response->statusDetail);
                $payment->setAdditionalInformation('threeDStatus', $response->{'3DSecure'}->status);
                $payment->save();

                //invoice
                $payment->getMethodInstance()->markAsInitialized();
                $order->place()->save();

                //send email
                $this->_checkoutHelper->sendOrderEmail($order);

                //create transaction record
                switch ($this->_config->getSagepayPaymentAction()) {
                    case \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT:
                        $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                        $closed = true;
                        break;
                    default:
                        $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                        $closed = true;
                        break;
                }
                $transaction = $this->_transactionFactory->create();
                $transaction->setOrderPaymentObject($payment);
                $transaction->setTxnId($this->_transactionId);
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

                //prepare session to success page
                $this->_checkoutSession->clearHelperData();
                $this->_checkoutSession->setLastQuoteId($quoteId);
                $this->_checkoutSession->setLastSuccessQuoteId($quoteId);
                $this->_checkoutSession->setLastOrderId($order->getId());
                $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->_checkoutSession->setLastOrderStatus($order->getStatus());
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order'));
            }
        } else {
            throw new \Magento\Framework\Validator\Exception(__('Invalid Sage Pay response'));
        }
    }

    protected function _javascriptRedirect($url)
    {
        //redirect to success via javascript
        $this->getResponse()->setBody(
            '<script>window.top.location.href = "'
            . $this->_url->getUrl($url, ['_secure' => true])
            . '";</script>'
        );
    }
}
