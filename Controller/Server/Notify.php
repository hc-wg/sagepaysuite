<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Server;

use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Ebizmarts\SagePaySuite\Model\Token;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Validator\Exception;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;

class Notify extends Action
{

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $suiteLogger;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order;

    /**
     * @var array
     */
    private $postData;

    /**
     * @var Token
     */
    private $tokenModel;

    /** @var OrderUpdateOnCallback */
    private $updateOrderCallback;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * Notify constructor.
     * @param Context $context
     * @param Logger $suiteLogger
     * @param OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param Config $config
     * @param Session $checkoutSession
     * @param Token $tokenModel
     * @param Quote $quote
     * @param OrderUpdateOnCallback $updateOrderCallback
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        Context $context,
        Logger $suiteLogger,
        OrderFactory $orderFactory,
        OrderSender $orderSender,
        Config $config,
        Session $checkoutSession,
        Token $tokenModel,
        Quote $quote,
        OrderUpdateOnCallback $updateOrderCallback,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
    
        parent::__construct($context);

        $this->suiteLogger         = $suiteLogger;
        $this->updateOrderCallback = $updateOrderCallback;
        $this->orderFactory        = $orderFactory;
        $this->orderSender         = $orderSender;
        $this->config              = $config;
        $this->checkoutSession     = $checkoutSession;
        $this->tokenModel          = $tokenModel;
        $this->quote               = $quote;
        $this->quoteIdMaskFactory  = $quoteIdMaskFactory;
        $this->config->setMethodCode(Config::METHOD_SERVER);
    }

    public function execute()
    {
        //get data from request
        $this->postData = $this->getRequest()->getPost();
        $this->quote    = $this->quote->load($this->getRequest()->getParam("quoteid"));

        //log response
        $this->suiteLogger->sageLog(Logger::LOG_REQUEST, $this->postData, [__METHOD__, __LINE__]);

        try {
            //find quote with GET param
            if (empty($this->quote->getId())) {
                return $this->returnInvalid("Unable to find quote");
            }

            //find order with quote id
            $order = $this->orderFactory->create()->loadByIncrementId($this->quote->getReservedOrderId());
            if ($order === null || $order->getId() === null) {
                return $this->returnInvalid("Order was not found");
            }
            $this->order = $order;
            $payment     = $order->getPayment();

//            if ($this->order->getStatus() !== \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
//                return $this->returnOk();
//            }

            //get some vars from POST
            $status = $this->postData->Status;
            $transactionId = str_replace("{", "", str_replace("}", "", $this->postData->VPSTxId)); //strip brackets

            //validate hash
            $this->validateSignature($payment);

            //update payment details
            if (!empty($transactionId) && $payment->getLastTransId() == $transactionId) { //validate transaction id
                $payment->setAdditionalInformation('statusDetail', $this->postData->StatusDetail);
                $payment->setAdditionalInformation('threeDStatus', $this->postData->{'3DSecureStatus'});
                $payment->setCcType($this->postData->CardType);
                $payment->setCcLast4($this->postData->Last4Digits);
                $payment->setCcExpMonth(substr($this->postData->ExpiryDate, 0, 2));
                $payment->setCcExpYear(substr($this->postData->ExpiryDate, 2));
                $payment->save();
            } else {
                throw new Exception(__('Invalid transaction id'));
            }

            $this->persistToken($order);

            if ($status == "ABORT") { //Transaction canceled by customer

                //cancel pending payment order
                $this->cancelOrder($order);
                return $this->returnAbort($this->quote->getId());
            } elseif ($status == "OK" || $status == "AUTHENTICATED" || $status == "REGISTERED") {
                $this->updateOrderCallback->setOrder($this->order);
                $this->updateOrderCallback->confirmPayment($transactionId);
                return $this->returnOk();
            } elseif ($status == "PENDING") {
                //Transaction in PENDING state (this is just for Euro Payments)

                $payment->setAdditionalInformation('euroPayment', true);

                //send order email
                $this->orderSender->send($this->order);

                return $this->returnOk();
            } else { //Transaction failed with NOTAUTHED, REJECTED or ERROR

                //cancel pending payment order
                $this->cancelOrder($order);

                return $this->returnInvalid("Payment was not accepted, please try another payment method");
            }
        } catch (ApiException $apiException) {
            $this->suiteLogger->logException($apiException, [__METHOD__, __LINE__]);

            //cancel pending payment order
            $this->cancelOrder($order);

            return $this->returnInvalid("Something went wrong: " . $apiException->getUserMessage());
        } catch (\Exception $e) {
            $this->suiteLogger->logException($e, [__METHOD__, __LINE__]);

            //cancel pending payment order
            $this->cancelOrder($order);

            return $this->returnInvalid("Something went wrong: " . $e->getMessage());
        }
    }

    private function getVPSSignatureString($payment)
    {
        return $this->postData->VPSTxId .
        $this->postData->VendorTxCode .
        $this->postData->Status .
        (property_exists($this->postData, 'TxAuthNo') === true ? $this->postData->TxAuthNo : '') .
        strtolower($payment->getAdditionalInformation('vendorname')) .
        $this->postData->AVSCV2 .
        $payment->getAdditionalInformation('securityKey') .
        $this->postData->AddressResult .
        $this->postData->PostCodeResult .
        $this->postData->CV2Result .
        $this->postData->GiftAid .
        $this->postData->{'3DSecureStatus'} .
        (property_exists($this->postData, 'CAVV') === true ? $this->postData->CAVV : '') .
        $this->postData->AddressStatus .
        $this->postData->PayerStatus .
        $this->postData->CardType .
        $this->postData->Last4Digits .
        (property_exists($this->postData, 'DeclineCode') === true ? $this->postData->DeclineCode : '') .
        $this->postData->ExpiryDate .
        (property_exists($this->postData, 'FraudResponse') === true ? $this->postData->FraudResponse : '') .
        (property_exists($this->postData, 'BankAuthCode') === true ? $this->postData->BankAuthCode : '');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    private function cancelOrder($order)
    {
        try {
            $order->cancel()->save();
        } catch (\Exception $e) {
            $this->suiteLogger->logException($e, [__METHOD__, __LINE__]);
        }
    }

    private function returnAbort($quoteId = null)
    {
        $strResponse = 'Status=OK' . "\r\n";
        $strResponse .= 'StatusDetail=Transaction ABORTED successfully' . "\r\n";
        $strResponse .= 'RedirectURL=' . $this->getAbortRedirectUrl($quoteId) . "\r\n";

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        //log our response
        $this->suiteLogger->sageLog(Logger::LOG_REQUEST, $strResponse, [__METHOD__, __LINE__]);
    }

    private function returnOk()
    {
        $strResponse = 'Status=OK' . "\r\n";
        $strResponse .= 'StatusDetail=Transaction completed successfully' . "\r\n";
        $strResponse .= 'RedirectURL=' . $this->getSuccessRedirectUrl() . "\r\n";

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        //log our response
        $this->suiteLogger->sageLog(Logger::LOG_REQUEST, $strResponse, [__METHOD__, __LINE__]);
    }

    private function returnInvalid($message = 'Invalid transaction, please try another payment method')
    {
        $strResponse = 'Status=INVALID' . "\r\n";
        $strResponse .= 'StatusDetail=' . $message . "\r\n";
        $strResponse .= 'RedirectURL=' . $this->getFailedRedirectUrl($message) . "\r\n";

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        //log our response
        $this->suiteLogger->sageLog(Logger::LOG_REQUEST, $strResponse, [__METHOD__, __LINE__]);
    }

    private function getAbortRedirectUrl($quoteId = null)
    {
        $url = $this->_url->getUrl('*/*/cancel', [
            '_secure' => true,
            //'_store' => $this->getRequest()->getParam('_store') @codingStandardsIgnoreLine
        ]);

        $url .= "?quote={$quoteId}&message=Transaction cancelled by customer";

        return $url;
    }

    private function getSuccessRedirectUrl()
    {
        $url = $this->_url->getUrl('*/*/success', [
            '_secure' => true,
        ]);

        $url .= "?quoteid=" . $this->quote->getId();

        return $url;
    }

    private function getFailedRedirectUrl($message)
    {
        $url = $this->_url->getUrl('*/*/cancel', [
            '_secure' => true,
        ]);

        $url .= "?message=" . $message;

        return $url;
    }

    /**
     * @param $payment
     * @throws Exception
     */
    private function validateSignature($payment)
    {
        $localMd5Hash = hash('md5', $this->getVPSSignatureString($payment));

        if (strtoupper($localMd5Hash) != $this->postData->VPSSignature) {
            $this->suiteLogger->sageLog(
                Logger::LOG_REQUEST,
                "INVALID SIGNATURE: " . $this->getVPSSignatureString($payment),
                [__METHOD__, __LINE__]
            );
            throw new Exception(__('Invalid VPS Signature'));
        }
    }

    /**
     * @param $order
     */
    private function persistToken($order)
    {
        if (isset($this->postData->Token)) {
            //save token

            $this->tokenModel->saveToken(
                $order->getCustomerId(),
                $this->postData->Token,
                $this->postData->CardType,
                $this->postData->Last4Digits,
                substr($this->postData->ExpiryDate, 0, 2),
                substr($this->postData->ExpiryDate, 2),
                $this->config->getVendorname()
            );
        }
    }
}
