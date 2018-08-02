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
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Config\SagePayCardType;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\PiRequest;
use Magento\Checkout\Model\Session;
use Magento\Framework\Validator\Exception as ValidatorException;
use Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory;
use Magento\Sales\Model\Order\Payment\TransactionFactory;

class EcommerceManagement extends RequestManagement
{
    /** @var Session */
    private $checkoutSession;

    private $sagePaySuiteLogger;

    private $actionFactory;

    /** @var TransactionFactory */
    private $transactionFactory;

    public function __construct(
        Checkout $checkoutHelper,
        PIRest $piRestApi,
        SagePayCardType $ccConvert,
        PiRequest $piRequest,
        Data $suiteHelper,
        PiResultInterface $result,
        Session $checkoutSession,
        Logger $sagePaySuiteLogger,
        ClosedForActionFactory $actionFactory,
        TransactionFactory $transactionFactory
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
        $this->actionFactory      = $actionFactory;
        $this->transactionFactory = $transactionFactory;
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
            $this->tryToChargeCustomerAndCreateOrder();
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
            $this->checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", $order->getId());

            $payment = $order->getPayment();
            $payment->setTransactionId($this->getPayResult()->getTransactionId());
            $payment->setLastTransId($this->getPayResult()->getTransactionId());
            $payment->save();

            $this->createInvoiceForSuccessPayment($payment, $order);
        } else {
            throw new ValidatorException(__('Unable to save Sage Pay order'));
        }

        $this->getResult()->setSuccess(true);
        $this->getResult()->setTransactionId($this->getPayResult()->getTransactionId());
        $this->getResult()->setStatus($this->getPayResult()->getStatus());

        //additional details required for callback URL
        $this->getResult()->setOrderId($order->getId());
        $this->getResult()->setQuoteId($this->getQuote()->getId());

        if ($this->getPayResult()->getStatusCode() == Config::AUTH3D_REQUIRED_STATUS) {
            $this->getResult()->setParEq($this->getPayResult()->getParEq());
            $this->getResult()->setAcsUrl($this->getPayResult()->getAcsUrl());
        }
    }

    /**
     * @param $payment
     * @param $order
     */
    private function createInvoiceForSuccessPayment($payment, $order)
    {
        //invoice
        if ($this->getPayResult()->getStatusCode() === Config::SUCCESS_STATUS) {
            $request = $this->getRequest();
            $sagePayPaymentAction = $request['transactionType'];
            if ($sagePayPaymentAction === Config::ACTION_PAYMENT_PI) {
                $payment->getMethodInstance()->markAsInitialized();
            }
            $order->place()->save();

            $this->getCheckoutHelper()->sendOrderEmail($order);

            if ($sagePayPaymentAction === Config::ACTION_DEFER_PI) {
                /** @var \Ebizmarts\SagePaySuite\Model\Config\ClosedForAction $actionClosed */
                $actionClosed = $this->actionFactory->create(['paymentAction' => $sagePayPaymentAction]);
                list($action, $closed) = $actionClosed->getActionClosedForPaymentAction();

                /** @var \Magento\Sales\Model\Order\Payment\Transaction $transaction */
                $transaction = $this->transactionFactory->create();
                $transaction->setOrderPaymentObject($payment);
                $transaction->setTxnId($this->getPayResult()->getTransactionId());
                $transaction->setOrderId($order->getEntityId());
                $transaction->setTxnType($action);
                $transaction->setPaymentId($payment->getId());
                $transaction->setIsClosed($closed);
                $transaction->save();
            }

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
        $this->sagePaySuiteLogger->logException($exceptionObject);
        $this->getResult()->setSuccess(false);
        $this->getResult()->setErrorMessage(__("Something went wrong: %1", $exceptionObject->getMessage()));

        if ($this->getPayResult() !== null && $this->getPayResult()->getStatusCode() == "0000") {
            $this->getPiRestApi()->void($this->getPayResult()->getTransactionId());
        }
    }
}
