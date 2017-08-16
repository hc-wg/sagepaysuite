<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Paypal;

use Ebizmarts\SagePaySuite\Helper\Checkout;
use Ebizmarts\SagePaySuite\Helper\Data as SuiteHelper;
use Ebizmarts\SagePaySuite\Model\Api\Post;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;

class Callback extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Config
     */
    private $_config;

    /**
     * @var Quote
     */
    private $_quote;

    /**
     * @var Session
     */
    private $_checkoutSession;

    /**
     * @var Checkout
     */
    private $_checkoutHelper;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    private $_postData;

    /** @var OrderFactory */
    private $_orderFactory;

    /** @var Post */
    private $_postApi;

    /** @var \Magento\Sales\Model\Order */
    private $_order;

    /**
     * @var QuoteFactory
     */
    private $_quoteFactory;

    /** @var OrderUpdateOnCallback */
    private $updateOrderCallback;

    /** @var SuiteHelper */
    private $suiteHelper;

    /**
     * Callback constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param Config $config
     * @param Logger $suiteLogger
     * @param Checkout $checkoutHelper
     * @param Post $postApi
     * @param Quote $quote
     * @param OrderFactory $orderFactory
     * @param QuoteFactory $quoteFactory
     * @param OrderUpdateOnCallback $updateOrderCallback
     * @param SuiteHelper $suiteHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $config,
        Logger $suiteLogger,
        Checkout $checkoutHelper,
        Post $postApi,
        Quote $quote,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        OrderUpdateOnCallback $updateOrderCallback,
        SuiteHelper $suiteHelper
    ) {
    
        parent::__construct($context);
        $this->_config             = $config;
        $this->_checkoutSession    = $checkoutSession;
        $this->_checkoutHelper     = $checkoutHelper;
        $this->_suiteLogger        = $suiteLogger;
        $this->_postApi            = $postApi;
        $this->_quote              = $quote;
        $this->_orderFactory       = $orderFactory;
        $this->_quoteFactory       = $quoteFactory;
        $this->updateOrderCallback = $updateOrderCallback;
        $this->suiteHelper         = $suiteHelper;

        $this->_config->setMethodCode(Config::METHOD_PAYPAL);
    }

    /**
     * Paypal callback
     * @throws LocalizedException
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            //get POST data
            $this->_postData = $this->getRequest()->getPost();

            //log response
            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $this->_postData, [__METHOD__, __LINE__]);

            $this->validatePostDataStatusAndStatusDetail();

            $this->loadQuoteFromDataSource();

            $order = $this->loadOrderFromDataSource();

            $completionResponse = $this->_sendCompletionPost()["data"];

            $transactionId = $completionResponse["VPSTxId"];
            $transactionId = $this->suiteHelper->removeCurlyBraces($transactionId);

            $payment = $order->getPayment();

            $this->updatePaymentInformation($transactionId, $payment, $completionResponse);

            $this->updateOrderCallback->setOrder($this->_order);
            $this->updateOrderCallback->confirmPayment($transactionId);

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
            $this->_suiteLogger->logException($e);
            $this->_redirectToCartAndShowError('We can\'t place the order: ' . $e->getMessage());
        }
    }

    private function _sendCompletionPost()
    {
        $request = [
            "VPSProtocol" => $this->_config->getVPSProtocol(),
            "TxType"      => "COMPLETE",
            "VPSTxId"     => $this->_postData->VPSTxId,
            "Amount"      => $this->getAuthorisedAmount(),
            "Accept"      => "YES"
        ];

        return $this->_postApi->sendPost(
            $request,
            $this->_getServiceURL(),
            ["OK", 'REGISTERED', 'AUTHENTICATED'],
            'Invalid response from PayPal'
        );
    }

    private function getAuthorisedAmount()
    {
        $quoteAmount = $this->_config->getQuoteAmount($this->_quote);
        $amount = number_format($quoteAmount, 2, '.', '');
        return $amount;
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
        if ($this->_config->getMode() == Config::MODE_LIVE) {
            return Config::URL_PAYPAL_COMPLETION_LIVE;
        } else {
            return Config::URL_PAYPAL_COMPLETION_TEST;
        }
    }

    private function validatePostDataStatusAndStatusDetail()
    {
        if (empty($this->_postData) || !isset($this->_postData->Status) || $this->_postData->Status != "PAYPALOK") {
            if (!empty($this->_postData) && isset($this->_postData->StatusDetail)) {
                throw new LocalizedException(__("Can not place PayPal order: " . $this->_postData->StatusDetail));
            } else {
                throw new LocalizedException(__("Can not place PayPal order, please try another payment method"));
            }
        }
    }

    private function loadQuoteFromDataSource()
    {
        $this->_quote = $this->_quoteFactory->create()->load($this->getRequest()->getParam("quoteid"));
        if (empty($this->_quote->getId())) {
            throw new LocalizedException(__("Unable to find payment data."));
        }
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    private function loadOrderFromDataSource()
    {
        $order = $this->_order = $this->_orderFactory->create()->loadByIncrementId($this->_quote->getReservedOrderId());
        if ($order === null || $order->getId() === null) {
            throw new LocalizedException(__("Invalid order."));
        }

        return $order;
    }

    /**
     * @param $transactionId
     * @param $payment
     * @param $completionResponse
     * @throws ValidatorException
     */
    private function updatePaymentInformation($transactionId, $payment, $completionResponse)
    {
        if (!empty($transactionId) && $payment->getLastTransId() == $transactionId) {
            $payment->setAdditionalInformation('statusDetail', $completionResponse['StatusDetail']);
            $payment->setAdditionalInformation('threeDStatus', $completionResponse['3DSecureStatus']);
            $payment->setCcType("PayPal");
            $payment->setLastTransId($transactionId);
            $payment->save();
        } else {
            throw new ValidatorException(__('Invalid transaction id'));
        }
    }
}
