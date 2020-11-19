<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;

class ThreeDSecureCallbackManagement extends RequestManagement
{
    const NUM_OF_ATTEMPTS = 5;

    const RETRY_INTERVAL = 6000000;

    /** @var \Magento\Checkout\Model\Session */
    private $checkoutSession;

    /** @var \Magento\Framework\App\RequestInterface */
    private $httpRequest;

    /** @var \Magento\Sales\Model\OrderFactory */
    private $orderFactory;

    /** @var \Magento\Sales\Model\Order */
    private $order;

    /** @var \Magento\Sales\Model\Order\Payment\TransactionFactory */
    private $transactionFactory;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory */
    private $payResultFactory;

    /** @var InvoiceSender */
    private $invoiceEmailSender;

    /** @var Config */
    private $config;

    /** @var CryptAndCodeData */
    private $cryptAndCode;

    public function __construct(
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $piRestApi,
        \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType $ccConvert,
        \Ebizmarts\SagePaySuite\Model\PiRequest $piRequest,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface $result,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\RequestInterface $httpRequest,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory $payResultFactory,
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

        $this->httpRequest        = $httpRequest;
        $this->checkoutSession    = $checkoutSession;
        $this->orderFactory       = $orderFactory;
        $this->transactionFactory = $transactionFactory;
        $this->payResultFactory   = $payResultFactory;
        $this->invoiceEmailSender = $invoiceEmailSender;
        $this->config             = $config;
        $this->cryptAndCode       = $cryptAndCode;
    }

    public function getPayment()
    {
        return $this->order->getPayment();
    }

    /**
     * @return PiTransactionResultInterface
     */
    public function pay()
    {
        $payResult = $this->payResultFactory->create();
        $this->setPayResult($payResult);
        $cres = $this->getRequestData()->getCres();

        if (isset($cres)) {
            /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD $submit3Dv2Result */
            $submit3DResult = $this->getPiRestApi()->submit3Dv2(
                $cres,
                $this->getRequestData()->getTransactionId()
            );
        } else {
            /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD $submit3DResult */
            $submit3DResult = $this->getPiRestApi()->submit3D(
                $this->getRequestData()->getParEs(),
                $this->getRequestData()->getTransactionId()
            );
        }

        $this->getPayResult()->setStatus($submit3DResult->getStatus());

        return $this->getPayResult();
    }

    /**
     * @return boolean
     */
    public function getIsMotoTransaction()
    {
        return false;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface
     */
    public function placeOrder()
    {
        $payResult = $this->pay();

        if ($payResult->getStatus() == 'Operation not allowed') {
            $this->getResult()->setErrorMessage(null);
        } elseif ($payResult->getStatus() !== null) {
            $transactionDetailsResult = $this->retrieveTransactionDetails();

            $this->_confirmPayment($transactionDetailsResult);

            //remove order pre-saved flag from checkout
            $this->checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);
        } else {
            $this->getResult()->setErrorMessage("Invalid 3D secure authentication.");
        }

        return $this->getResult();
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult
     */
    private function retrieveTransactionDetails()
    {
        $attempts = 0;
        $transactionDetailsResult = null;

        $vpsTxId = $this->getRequestData()->getTransactionId();

        do {
            try {
                /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult $transactionDetailsResult */
                $transactionDetailsResult = $this->getPiRestApi()->transactionDetails($vpsTxId);
            } catch (ApiException $e) {
                $attempts++;
                usleep(self::RETRY_INTERVAL);
                continue;
            }
        } while ($attempts < self::NUM_OF_ATTEMPTS && $transactionDetailsResult === null);

        if (null === $transactionDetailsResult) {
            $this->getPiRestApi()->void($vpsTxId);
            throw new \LogicException("Could not retrieve transaction details");
        }

        return $transactionDetailsResult;
    }

    /**
     * @param PiTransactionResultInterface $response
     * @throws ValidatorException
     */
    private function _confirmPayment(PiTransactionResultInterface $response)
    {
        if ($response->getStatusCode() == Config::SUCCESS_STATUS) {
            $orderId = $this->httpRequest->getParam("orderId");
            $orderId = $this->decodeAndDecrypt($orderId);

            $quoteId = $this->httpRequest->getParam("quoteId");
            $quoteId = $this->decodeAndDecrypt($quoteId);

            $this->order   = $this->orderFactory->create()->load($orderId);

            if (!empty($this->order)) {
                $this->getPayResult()->setPaymentMethod($response->getPaymentMethod());
                $this->getPayResult()->setStatusDetail($response->getStatusDetail());
                $this->getPayResult()->setStatusCode($response->getStatusCode());
                $this->getPayResult()->setThreeDSecure($response->getThreeDSecure());
                $this->getPayResult()->setTransactionId($response->getTransactionId());
                $this->getPayResult()->setAvsCvcCheck($response->getAvsCvcCheck());

                $this->processPayment();

                $payment = $this->getPayment();

                $payment->save();

                //invoice
                $payment->getMethodInstance()->markAsInitialized();
                $this->order->place()->save();

                //send email
                $this->getCheckoutHelper()->sendOrderEmail($this->order);
                $this->sendInvoiceNotification($this->order);

                //update invoice transaction id
                $this->order->getInvoiceCollection()
                    ->setDataToAll('transaction_id', $payment->getLastTransId())
                    ->save();

                //prepare session to success page
                $this->checkoutSession->clearHelperData();
                $this->checkoutSession->setLastQuoteId($quoteId);
                $this->checkoutSession->setLastSuccessQuoteId($quoteId);
                $this->checkoutSession->setLastOrderId($this->order->getId());
                $this->checkoutSession->setLastRealOrderId($this->order->getIncrementId());
                $this->checkoutSession->setLastOrderStatus($this->order->getStatus());
            } else {
                throw new ValidatorException(__('Unable to save Opayo order'));
            }
        } else {
            throw new ValidatorException(__('Invalid Opayo response: %1', $response->getStatusDetail()));
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
     * @param $data
     * @return string
     */
    public function decodeAndDecrypt($data)
    {
        return $this->cryptAndCode->decodeAndDecrypt($data);
    }
}
