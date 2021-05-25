<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Ebizmarts\SagePaySuite\Helper\Checkout;
use Ebizmarts\SagePaySuite\Helper\Data as SuiteHelper;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Form;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote;

    /**
     * @var Session
     */
    private $_checkoutSession;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var Form
     */
    private $_formModel;

    /**
     * @var QuoteFactory
     */
    private $_quoteFactory;

    /**
     * @var OrderFactory
     */
    private $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /** @var OrderSender */
    private $orderSender;

    /** @var OrderUpdateOnCallback */
    private $updateOrderCallback;

    /**
     * @var SuiteHelper
     */
    private $suiteHelper;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Success constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param Logger $suiteLogger
     * @param Checkout $checkoutHelper
     * @param Form $formModel
     * @param QuoteFactory $quoteFactory
     * @param OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param OrderUpdateOnCallback $updateOrderCallback
     * @param SuiteHelper $suiteHelper
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Logger $suiteLogger,
        Checkout $checkoutHelper,
        Form $formModel,
        QuoteFactory $quoteFactory,
        OrderFactory $orderFactory,
        OrderSender $orderSender,
        OrderUpdateOnCallback $updateOrderCallback,
        SuiteHelper $suiteHelper,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->_checkoutSession    = $checkoutSession;
        $this->_quoteFactory       = $quoteFactory;
        $this->_suiteLogger        = $suiteLogger;
        $this->_formModel          = $formModel;
        $this->_orderFactory       = $orderFactory;
        $this->orderSender         = $orderSender;
        $this->updateOrderCallback = $updateOrderCallback;
        $this->suiteHelper         = $suiteHelper;
        $this->encryptor           = $encryptor;
    }

    /**
     * FORM success callback
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            $response = $this->_formModel->decodeSagePayResponse($this->getRequest()->getParam("crypt"));
            if (!isset($response["VPSTxId"])) {
                throw new LocalizedException(__('Invalid response from Opayo.'));
            }

            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $response, [__METHOD__, __LINE__]);

            $quoteId = $this->encryptor->decrypt($this->getRequest()->getParam("quoteid"));
            $this->_quote = $this->_quoteFactory->create()->load($quoteId);
            $this->_suiteLogger->debugLog($this->_quote->getData(), [__METHOD__, __LINE__]);

            $this->_order = $this->_orderFactory->create()->loadByIncrementId($this->_quote->getReservedOrderId());
            if ($this->_order === null || $this->_order->getId() === null) {
                throw new LocalizedException(__('Order not available.'));
            }
            $this->_suiteLogger->debugLog($this->_order->getData(), [__METHOD__, __LINE__]);

            $transactionId = $response["VPSTxId"];
            $transactionId = $this->suiteHelper->removeCurlyBraces($transactionId); //strip brackets

            $payment = $this->_order->getPayment();

            $vendorTxCode = $payment->getAdditionalInformation("vendorTxCode");

            $isDuplicated = $payment->getAdditionalInformation("Status") == Config::OK_STATUS;

            if (!$isDuplicated) {
                $this->_suiteLogger->debugLog(
                    'Payment VendorTxCode: ' . $vendorTxCode . ' Response VendorTxCode: ' . $response['VendorTxCode'],
                    [__METHOD__, __LINE__]
                );
                if (!empty($transactionId) && ($vendorTxCode == $response['VendorTxCode'])) {
                    foreach ($response as $name => $value) {
                        $payment->setTransactionAdditionalInfo($name, $value);
                        $payment->setAdditionalInformation($name, $value);
                    }

                    $payment->setLastTransId($transactionId);
                    $payment->setCcType($response['CardType']);
                    $payment->setCcLast4($response['Last4Digits']);
                    if (isset($response["ExpiryDate"])) {
                        $payment->setCcExpMonth(substr($response["ExpiryDate"], 0, 2));
                        $payment->setCcExpYear(substr($response["ExpiryDate"], 2));
                    }

                    $payment->save();
                    $this->_suiteLogger->debugLog($payment->getData(), [__METHOD__, __LINE__]);
                } else {
                    throw new \Magento\Framework\Validator\Exception(__('Invalid transaction id.'));
                }
            }

            $redirect = 'sagepaysuite/form/failure';
            $status   = $response['Status'];
            if ($status == Config::OK_STATUS || $status == Config::AUTHENTICATED_STATUS || $status == Config::REGISTERED_STATUS) {
                $this->updateOrderCallback->setOrder($this->_order);
                try {
                    $this->updateOrderCallback->confirmPayment($transactionId);
                } catch (AlreadyExistsException $ex) {
                    $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, "Sage Pay retry. $transactionId", [__METHOD__, __LINE__]);
                }
                $redirect = 'checkout/onepage/success';
            } elseif ($status == Config::PENDING_STATUS) {
                //Transaction in PENDING state (this is just for Euro Payments)
                $payment->setAdditionalInformation('euroPayment', true);

                //send order email
                $this->orderSender->send($this->_order);

                $redirect = 'checkout/onepage/success';
            } elseif ($isDuplicated) {
                $redirect = 'checkout/onepage/success';
            }

            //prepare session to success page
            $this->_checkoutSession->start();
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastSuccessQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastOrderId($this->_order->getId());
            $this->_checkoutSession->setLastRealOrderId($this->_order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($this->_order->getStatus());
            $this->_checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);
            $this->_checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::CONVERTING_QUOTE_TO_ORDER, 0);

            $this->_suiteLogger->orderEndLog($this->_order->getIncrementId(), $quoteId, $transactionId);

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
