<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Validator\Exception as ValidatorException;
use Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory;

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

    private $actionFactory;

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
        ClosedForActionFactory $actionFactory
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
        $this->actionFactory = $actionFactory;
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

        /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD $submit3DResult */
        $submit3DResult = $this->getPiRestApi()->submit3D(
            $this->getRequestData()->getParEs(),
            $this->getRequestData()->getTransactionId()
        );

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

        if ($payResult->getStatus() !== null) {

            $transactionDetailsResult = $this->retrieveTransactionDetails();

            $this->confirmPayment($transactionDetailsResult);

            //remove order pre-saved flag from checkout
            $this->checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", null);

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
    private function confirmPayment(PiTransactionResultInterface $response)
    {

        if ($response->getStatusCode() === Config::SUCCESS_STATUS) {
            $orderId = $this->httpRequest->getParam("orderId");
            $quoteId = $this->httpRequest->getParam("quoteId");
            $this->order   = $this->orderFactory->create()->load($orderId);

            if (!empty($this->order)) {
                $this->getPayResult()->setPaymentMethod($response->getPaymentMethod());
                $this->getPayResult()->setStatusDetail($response->getStatusDetail());
                $this->getPayResult()->setStatusCode($response->getStatusCode());
                $this->getPayResult()->setThreeDSecure($response->getThreeDSecure());
                $this->getPayResult()->setTransactionId($response->getTransactionId());

                $this->processPayment();

                $payment = $this->getPayment();

                $payment->save();

                $sagePayPaymentAction = $this->getRequestData()->getPaymentAction();
                //invoice
                if ($sagePayPaymentAction === Config::ACTION_PAYMENT_PI) {
                    $payment->getMethodInstance()->markAsInitialized();
                }
                $this->order->place()->save();

                //send email
                $this->getCheckoutHelper()->sendOrderEmail($this->order);

                /** @var \Ebizmarts\SagePaySuite\Model\Config\ClosedForAction $actionClosed */
                $actionClosed = $this->actionFactory->create(['paymentAction' => $sagePayPaymentAction]);
                list($action, $closed) = $actionClosed->getActionClosedForPaymentAction();

                /** @var \Magento\Sales\Model\Order\Payment\Transaction $transaction */
                $transaction = $this->transactionFactory->create();
                $transaction->setOrderPaymentObject($payment);
                $transaction->setTxnId($this->getPayResult()->getTransactionId());
                $transaction->setOrderId($this->order->getEntityId());
                $transaction->setTxnType($action);
                $transaction->setPaymentId($payment->getId());
                $transaction->setIsClosed($closed);
                $transaction->save();

                //update invoice transaction id
                if ($sagePayPaymentAction === Config::ACTION_PAYMENT_PI) {
                    $this->order->getInvoiceCollection()->setDataToAll(
                        'transaction_id',
                        $payment->getLastTransId()
                    )->save();
                }

                //prepare session to success page
                $this->checkoutSession->clearHelperData();
                $this->checkoutSession->setLastQuoteId($quoteId);
                $this->checkoutSession->setLastSuccessQuoteId($quoteId);
                $this->checkoutSession->setLastOrderId($this->order->getId());
                $this->checkoutSession->setLastRealOrderId($this->order->getIncrementId());
                $this->checkoutSession->setLastOrderStatus($this->order->getStatus());
            } else {
                throw new ValidatorException(__('Unable to save Sage Pay order'));
            }
        } else {
            throw new ValidatorException(__('Invalid Sage Pay response: %1', $response->getStatusDetail()));
        }
    }
}
