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
    private $_pirestapi;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    private $_checkoutHelper;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    private $_transactionFactory;

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

    private $_transactionId;

    /** @var \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType */
    private $ccConverter;

    /**
     * Callback3D constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirestapi
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
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
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType $ccConvert
    ) {
    
        parent::__construct($context);

        $this->_pirestapi          = $pirestapi;
        $this->_suiteLogger        = $suiteLogger;
        $this->_orderFactory       = $orderFactory;
        $this->_checkoutHelper     = $checkoutHelper;
        $this->_transactionFactory = $transactionFactory;
        $this->_config             = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_logger             = $logger;
        $this->_checkoutSession    = $checkoutSession;
        $this->ccConverter         = $ccConvert;
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

            if (isset($submit3D_result->status)) {
                //request transaction details to confirm payment
                $transactionDetailsResult = $this->_pirestapi->transactionDetails($this->_transactionId);

                //log transaction details response
                $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $transactionDetailsResult, [__METHOD__, __LINE__]);

                $this->_confirmPayment($transactionDetailsResult);

                //remove order pre-saved flag from checkout
                $this->_checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", null);

                //redirect to success via javascript
                $this->_javascriptRedirect('checkout/onepage/success');
            } else {
                $this->messageManager->addError("Invalid 3D secure authentication.");
                $this->_javascriptRedirect('checkout/cart');
            }
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            $this->messageManager->addError($apiException->getUserMessage());
            $this->_javascriptRedirect('checkout/cart');
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->messageManager->addError("Something went wrong: " . $e->getMessage());
            $this->_javascriptRedirect('checkout/cart');
        }
    }

    private function _confirmPayment($response)
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

                $this->updateCardDataOnPayment($response, $payment);

                $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
                $payment->setAdditionalInformation('mode', $this->_config->getMode());
                $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());


                $payment->save();

                //invoice
                $payment->getMethodInstance()->markAsInitialized();
                $order->place()->save();

                //send email
                $this->_checkoutHelper->sendOrderEmail($order);

                $transaction = $this->_transactionFactory->create();
                $transaction->setOrderPaymentObject($payment);
                $transaction->setTxnId($this->_transactionId);
                $transaction->setOrderId($order->getEntityId());
                $transaction->setTxnType(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
                $transaction->setPaymentId($payment->getId());
                $transaction->setIsClosed(true);
                $transaction->save();

                //update invoice transaction id
                $order->getInvoiceCollection()
                    ->setDataToAll('transaction_id', $payment->getLastTransId())
                    ->save();

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

    private function _javascriptRedirect($url)
    {
        //redirect to success via javascript
        $this->getResponse()->setBody(
            '<script>window.top.location.href = "'
            . $this->_url->getUrl($url, ['_secure' => true])
            . '";</script>'
        );
    }

    /**
     * @param $response
     * @param $payment
     */
    private function updateCardDataOnPayment($response, $payment)
    {
        if (isset($response->paymentMethod)) {
            if (isset($response->paymentMethod->card)) {
                $card = $response->paymentMethod->card;
                $payment->setCcLast4($card->lastFourDigits);
                $payment->setCcExpMonth(substr($card->expiryDate, 0, 2));
                $payment->setCcExpYear(substr($card->expiryDate, 2, 2));
                $payment->setCcType($this->ccConverter->convert($card->cardType));
            }
        }
    }
}
