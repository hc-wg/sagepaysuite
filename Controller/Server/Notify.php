<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Server;

use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Notify extends \Magento\Framework\App\Action\Action
{

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
     * @var OrderSender
     */
    private $_orderSender;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /**
     * @var array
     */
    private $_postData;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Token
     */
    private $_tokenModel;

    /** @var \Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback */
    private $updateOrderCallback;

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * Notify constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\SagePaySuite\Model\Token $tokenModel
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback $updateOrderCallback
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $suiteLogger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderSender $orderSender,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Model\Token $tokenModel,
        \Magento\Quote\Model\Quote $quote,
        \Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback $updateOrderCallback,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
    
        parent::__construct($context);

        $this->_suiteLogger        = $suiteLogger;
        $this->updateOrderCallback = $updateOrderCallback;
        $this->_orderFactory       = $orderFactory;
        $this->_orderSender        = $orderSender;
        $this->_config             = $config;
        $this->_checkoutSession    = $checkoutSession;
        $this->_tokenModel         = $tokenModel;
        $this->_quote              = $quote;
        $this->quoteIdMaskFactory  = $quoteIdMaskFactory;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
    }

    public function execute()
    {
        //get data from request
        $this->_postData = $this->getRequest()->getPost();
        $this->_quote = $this->_quote->load($this->getRequest()->getParam("quoteid"));

        //log response
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $this->_postData, [__METHOD__, __LINE__]);

        try {
            //find quote with GET param
            if (empty($this->_quote->getId())) {
                return $this->_returnInvalid("Unable to find quote");
            }

            //find order with quote id
            $order = $this->_orderFactory->create()->loadByIncrementId($this->_quote->getReservedOrderId());
            if ($order === null || $order->getId() === null) {
                return $this->_returnInvalid("Order was not found");
            }
            $this->_order = $order;
            $payment = $order->getPayment();

            //get some vars from POST
            $status = $this->_postData->Status;
            $transactionId = str_replace("{", "", str_replace("}", "", $this->_postData->VPSTxId)); //strip brackets

            //validate hash
            $this->validateSignature($payment);

            //update payment details
            if (!empty($transactionId) && $payment->getLastTransId() == $transactionId) { //validate transaction id
                $payment->setAdditionalInformation('statusDetail', $this->_postData->StatusDetail);
                $payment->setAdditionalInformation('threeDStatus', $this->_postData->{'3DSecureStatus'});
                $payment->setCcType($this->_postData->CardType);
                $payment->setCcLast4($this->_postData->Last4Digits);
                $payment->setCcExpMonth(substr($this->_postData->ExpiryDate, 0, 2));
                $payment->setCcExpYear(substr($this->_postData->ExpiryDate, 2));
                $payment->save();
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Invalid transaction id'));
            }

            $this->persistToken($order);

            if ($status == "ABORT") { //Transaction canceled by customer

                //cancel pending payment order
                $this->_cancelOrder($order);
                return $this->_returnAbort($this->_quote->getId());
            } elseif ($status == "OK" || $status == "AUTHENTICATED" || $status == "REGISTERED") {
                $this->updateOrderCallback->setOrder($this->_order);
                $this->updateOrderCallback->confirmPayment($transactionId);
                return $this->_returnOk();
            } elseif ($status == "PENDING") {
                //Transaction in PENDING state (this is just for Euro Payments)

                $payment->setAdditionalInformation('euroPayment', true);

                //send order email
                $this->_orderSender->send($this->_order);

                return $this->_returnOk();
            } else { //Transaction failed with NOTAUTHED, REJECTED or ERROR

                //cancel pending payment order
                $this->_cancelOrder($order);

                return $this->_returnInvalid("Payment was not accepted, please try another payment method");
            }
        } catch (ApiException $apiException) {
            $this->_suiteLogger->logException($apiException, [__METHOD__, __LINE__]);

            //cancel pending payment order
            $this->_cancelOrder($order);

            return $this->_returnInvalid("Something went wrong: " . $apiException->getUserMessage());
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e, [__METHOD__, __LINE__]);

            //cancel pending payment order
            $this->_cancelOrder($order);

            return $this->_returnInvalid("Something went wrong: " . $e->getMessage());
        }
    }

    private function _getVPSSignatureString($payment)
    {
        return $this->_postData->VPSTxId .
        $this->_postData->VendorTxCode .
        $this->_postData->Status .
        (property_exists($this->_postData, 'TxAuthNo') === true ? $this->_postData->TxAuthNo : '') .
        strtolower($payment->getAdditionalInformation('vendorname')) .
        $this->_postData->AVSCV2 .
        $payment->getAdditionalInformation('securityKey') .
        $this->_postData->AddressResult .
        $this->_postData->PostCodeResult .
        $this->_postData->CV2Result .
        $this->_postData->GiftAid .
        $this->_postData->{'3DSecureStatus'} .
        (property_exists($this->_postData, 'CAVV') === true ? $this->_postData->CAVV : '') .
        $this->_postData->AddressStatus .
        $this->_postData->PayerStatus .
        $this->_postData->CardType .
        $this->_postData->Last4Digits .
        (property_exists($this->_postData, 'DeclineCode') === true ? $this->_postData->DeclineCode : '') .
        $this->_postData->ExpiryDate .
        (property_exists($this->_postData, 'FraudResponse') === true ? $this->_postData->FraudResponse : '') .
        (property_exists($this->_postData, 'BankAuthCode') === true ? $this->_postData->BankAuthCode : '');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    private function _cancelOrder($order)
    {
        try {
            $order->cancel()->save();
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e, [__METHOD__, __LINE__]);
        }
    }

    private function _returnAbort($quoteId = null)
    {
        $strResponse = 'Status=OK' . "\r\n";
        $strResponse .= 'StatusDetail=Transaction ABORTED successfully' . "\r\n";
        $strResponse .= 'RedirectURL=' . $this->_getAbortRedirectUrl($quoteId) . "\r\n";

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        //log our response
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $strResponse, [__METHOD__, __LINE__]);
    }

    private function _returnOk()
    {
        $strResponse = 'Status=OK' . "\r\n";
        $strResponse .= 'StatusDetail=Transaction completed successfully' . "\r\n";
        $strResponse .= 'RedirectURL=' . $this->_getSuccessRedirectUrl() . "\r\n";

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        //log our response
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $strResponse, [__METHOD__, __LINE__]);
    }

    private function _returnInvalid($message = 'Invalid transaction, please try another payment method')
    {
        $strResponse = 'Status=INVALID' . "\r\n";
        $strResponse .= 'StatusDetail=' . $message . "\r\n";
        $strResponse .= 'RedirectURL=' . $this->_getFailedRedirectUrl($message) . "\r\n";

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        //log our response
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $strResponse, [__METHOD__, __LINE__]);
    }

    private function _getAbortRedirectUrl($quoteId = null)
    {
        $url = $this->_url->getUrl('*/*/cancel', [
            '_secure' => true,
            //'_store' => $this->getRequest()->getParam('_store') @codingStandardsIgnoreLine
        ]);

        $url .= "?quote={$quoteId}&message=Transaction cancelled by customer";

        return $url;
    }

    private function _getSuccessRedirectUrl()
    {
        $url = $this->_url->getUrl('*/*/success', [
            '_secure' => true,
        ]);

        $url .= "?quoteid=" . $this->_quote->getId();

        return $url;
    }

    private function _getFailedRedirectUrl($message)
    {
        $url = $this->_url->getUrl('*/*/cancel', [
            '_secure' => true,
        ]);

        $url .= "?message=" . $message;

        return $url;
    }

    /**
     * @param $payment
     * @throws \Magento\Framework\Validator\Exception
     */
    private function validateSignature($payment)
    {
        $localMd5Hash = hash('md5', $this->_getVPSSignatureString($payment));

        if (strtoupper($localMd5Hash) != $this->_postData->VPSSignature) {
            $this->_suiteLogger->sageLog(
                Logger::LOG_REQUEST,
                "INVALID SIGNATURE: " . $this->_getVPSSignatureString($payment),
                [__METHOD__, __LINE__]
            );
            throw new \Magento\Framework\Validator\Exception(__('Invalid VPS Signature'));
        }
    }

    /**
     * @param $order
     */
    private function persistToken($order)
    {
        if (isset($this->_postData->Token)) {
            //save token

            $this->_tokenModel->saveToken(
                $order->getCustomerId(),
                $this->_postData->Token,
                $this->_postData->CardType,
                $this->_postData->Last4Digits,
                substr($this->_postData->ExpiryDate, 0, 2),
                substr($this->_postData->ExpiryDate, 2),
                $this->_config->getVendorname()
            );
        }
    }
}
