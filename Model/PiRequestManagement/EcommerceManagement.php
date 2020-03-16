<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/27/17
 * Time: 12:18 PM
 */

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Api\Data\PiResultInterface;
use Ebizmarts\SagePaySuite\Helper\Checkout;
use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Api\PIRest;
use Ebizmarts\SagePaySuite\Model\Config\SagePayCardType;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\PiRequest;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;

class EcommerceManagement extends RequestManagement
{
    /** @var Session */
    private $checkoutSession;

    private $sagePaySuiteLogger;

    /** @var \Magento\Quote\Model\QuoteValidator */
    private $quoteValidator;

    /** @var InvoiceSender */
    private $invoiceEmailSender;

    /** @var Config */
    private $config;

    /** @var EncryptorInterface */
    private $encryptor;

    /** @var CryptAndCodeData */
    private $cryptAndCode;

    public function __construct(
        Checkout $checkoutHelper,
        PIRest $piRestApi,
        SagePayCardType $ccConvert,
        PiRequest $piRequest,
        Data $suiteHelper,
        PiResultInterface $result,
        Session $checkoutSession,
        Logger $sagePaySuiteLogger,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        InvoiceSender $invoiceEmailSender,
        Config $config,
        CryptAndCodeData $cryptAndCode
    ) {
        parent::__construct(
            $checkoutHelper,
            $piRestApi,
            $ccConvert,
            $piRequest,
            $suiteHelper,
            $result
        );
        $this->checkoutSession    = $checkoutSession;
        $this->sagePaySuiteLogger = $sagePaySuiteLogger;
        $this->quoteValidator     = $quoteValidator;
        $this->invoiceEmailSender = $invoiceEmailSender;
        $this->config             = $config;
        $this->cryptAndCode       = $cryptAndCode;
    }

    /**
     * @inheritDoc
     */
    public function getIsMotoTransaction()
    {
        return false;
    }

    public function placeOrder()
    {
        try {
            $this->quoteValidator->validateBeforeSubmit($this->getQuote());
            $this->tryToChargeCustomerAndCreateOrder();
        } catch (LocalizedException $quoteException) {
            $this->tryToVoidTransactionLogErrorAndUpdateResult($quoteException);
        } catch (ApiException $apiException) {
            $this->tryToVoidTransactionLogErrorAndUpdateResult($apiException);
        } catch (\Exception $e) {
            $this->tryToVoidTransactionLogErrorAndUpdateResult($e);
        }

        return $this->getResult();
    }

    private function tryToChargeCustomerAndCreateOrder()
    {
        $this->pay();

        $this->processPayment();

        //save order with pending payment
        $order = $this->getCheckoutHelper()->placeOrder($this->getQuote());

        if ($order !== null) {
            //set pre-saved order flag in checkout session
            $this->checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, $order->getId());
            $this->checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::CONVERTING_QUOTE_TO_ORDER, 1);

            $payment = $order->getPayment();
            $payment->setTransactionId($this->getPayResult()->getTransactionId());
            $payment->setLastTransId($this->getPayResult()->getTransactionId());
            $payment->save();

            $this->createInvoiceForSuccessPayment($payment, $order);
        } else {
            throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order'));
        }

        $this->getResult()->setSuccess(true);
        $this->getResult()->setTransactionId($this->getPayResult()->getTransactionId());
        $this->getResult()->setStatus($this->getPayResult()->getStatus());

        //additional details required for callback URL
        $orderId = $order->getId();
        $orderId = $this->encryptAndEncode($orderId);
        $this->getResult()->setOrderId($orderId);

        $quoteId = $this->getQuote()->getId();
        $quoteId = $this->encryptAndEncode($quoteId);
        $this->getResult()->setQuoteId($quoteId);

        if ($this->isThreeDResponse()) {
            $this->getResult()->setParEq($this->getPayResult()->getParEq());
            $this->getResult()->setCreq($this->getPayResult()->getCReq());
            $this->getResult()->setAcsUrl($this->getPayResult()->getAcsUrl());
        } else {
            $this->checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::CONVERTING_QUOTE_TO_ORDER, 0);
        }
    }

    /**
     * @param $payment
     * @param $order
     */
    private function createInvoiceForSuccessPayment($payment, $order)
    {
        //invoice
        if ($this->getPayResult()->getStatusCode() == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {
            $payment->getMethodInstance()->markAsInitialized();
            $order->place()->save();

            //send email
            $this->getCheckoutHelper()->sendOrderEmail($order);
            $this->sendInvoiceNotification($order);

            //prepare session to success page
            $this->checkoutSession->clearHelperData();
            //set last successful quote
            $this->checkoutSession->setLastQuoteId($this->getQuote()->getId());
            $this->checkoutSession->setLastSuccessQuoteId($this->getQuote()->getId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());
        }
    }

    /**
     * @param $exceptionObject
     */
    private function tryToVoidTransactionLogErrorAndUpdateResult($exceptionObject)
    {
        $this->sagePaySuiteLogger->logException($exceptionObject, [__METHOD__, __LINE__]);
        $this->getResult()->setSuccess(false);
        $this->getResult()->setErrorMessage(__("Something went wrong: %1", $exceptionObject->getMessage()));

        if ($this->getPayResult() !== null && $this->getPayResult()->getStatusCode() == "0000") {
            try {
                $this->getPiRestApi()->void($this->getPayResult()->getTransactionId());
            } catch (ApiException $apiException) {
                $this->sagePaySuiteLogger->logException($exceptionObject);
            }
        }
    }

    public function sendInvoiceNotification($order)
    {
        if ($this->invoiceConfirmationIsEnable() && $this->paymentActionIsCapture()) {
            $invoices = $order->getInvoiceCollection();
            if ($invoices->count() > 0) {
                $this->invoiceEmailSender->send($invoices->getFirstItem());
            }
        }
    }

    /**
     * @return bool
     */
    private function paymentActionIsCapture()
    {
        $sagePayPaymentAction = $this->config->getSagepayPaymentAction();
        return $sagePayPaymentAction === Config::ACTION_PAYMENT_PI;
    }

    /**
     * @return bool
     */
    private function invoiceConfirmationIsEnable()
    {
        return (string)$this->config->getInvoiceConfirmationNotification() === "1";
    }

    /**
     * @return bool
     */
    private function isThreeDResponse()
    {
        return $this->getPayResult()->getStatusCode() == Config::AUTH3D_REQUIRED_STATUS ||
            $this->getPayResult()->getStatusCode() == Config::AUTH3D_V2_REQUIRED_STATUS;
    }

    /**
     * @param $data
     * @return string
     */
    public function encryptAndEncode($data)
    {
        return $this->cryptAndCode->encryptAndEncode($data);
    }
}
