<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Success extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

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

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form
     */
    private $_formModel;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $_quoteFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /** @var \Magento\Sales\Model\Order\Email\Sender\OrderSender */
    private $orderSender;

    /** @var \Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback */
    private $updateOrderCallback;

    /**
     * Success constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     * @param \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper
     * @param \Ebizmarts\SagePaySuite\Model\Form $formModel
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback $updateOrderCallback
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Model\Form $formModel,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback $updateOrderCallback
    ) {
    
        parent::__construct($context);
        $this->_config             = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->_customerSession    = $customerSession;
        $this->_checkoutSession    = $checkoutSession;
        $this->_checkoutHelper     = $checkoutHelper;
        $this->_quoteFactory       = $quoteFactory;
        $this->_suiteLogger        = $suiteLogger;
        $this->_formModel          = $formModel;
        $this->_orderFactory       = $orderFactory;
        $this->orderSender         = $orderSender;
        $this->updateOrderCallback = $updateOrderCallback;
    }

    /**
     * FORM success callback
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            //decode response
            $response = $this->_formModel->decodeSagePayResponse($this->getRequest()->getParam("crypt"));
            if (!array_key_exists("VPSTxId", $response)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid response from Sage Pay.'));
            }

            //log response
            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $response, [__METHOD__, __LINE__]);

            $this->_quote = $this->_quoteFactory->create()->load($this->getRequest()->getParam("quoteid"));

            $this->_order = $this->_orderFactory->create()->loadByIncrementId($this->_quote->getReservedOrderId());
            if ($this->_order === null || $this->_order->getId() === null) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order not available.'));
            }

            $transactionId = $response["VPSTxId"];
            $transactionId = str_replace(["{", "}"], ["", ""], $transactionId); //strip brackets

            $payment = $this->_order->getPayment();

            $vendorTxCode = $payment->getAdditionalInformation("vendorTxCode");

            //update payment details
            if (!empty($transactionId) && ($vendorTxCode == $response['VendorTxCode'])) {
                $payment->setLastTransId($transactionId);
                $payment->setAdditionalInformation('statusDetail', $response['StatusDetail']);
                $payment->setAdditionalInformation('threeDStatus', $response['3DSecureStatus']);
                $payment->setCcType($response['CardType']);
                $payment->setCcLast4($response['Last4Digits']);
                if (array_key_exists("ExpiryDate", $response)) {
                    $payment->setCcExpMonth(substr($response["ExpiryDate"], 0, 2));
                    $payment->setCcExpYear(substr($response["ExpiryDate"], 2));
                }
                if (array_key_exists("3DSecureStatus", $response)) {
                    $payment->setAdditionalInformation('threeDStatus', $response["3DSecureStatus"]);
                }
                $payment->save();
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Invalid transaction id.'));
            }

            $redirect = 'sagepaysuite/form/failure';
            $status   = $response['Status'];
            if ($status == "OK" || $status == "AUTHENTICATED" || $status == "REGISTERED") {
                $this->updateOrderCallback->setOrder($this->_order);
                $this->updateOrderCallback->confirmPayment($transactionId);
                $redirect = 'checkout/onepage/success';
            } elseif ($status == "PENDING") {
                //Transaction in PENDING state (this is just for Euro Payments)
                $payment->setAdditionalInformation('euroPayment', true);

                //send order email
                $this->orderSender->send($this->_order);

                $redirect = 'checkout/onepage/success';
            }

            //prepare session to success page
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastSuccessQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastOrderId($this->_order->getId());
            $this->_checkoutSession->setLastRealOrderId($this->_order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($this->_order->getStatus());

            $this->_checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", null);

            return $this->_redirect($redirect);
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e);
            $this->_redirectToCartAndShowError(
                __('Your payment was successful but the order was NOT created, please contact us: %1', $e->getMessage())
            );
        }
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

}
