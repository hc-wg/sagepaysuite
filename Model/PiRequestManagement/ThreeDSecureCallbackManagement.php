<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;


class ThreeDSecureCallbackManagement extends RequestManagement
{
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
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory $payResultFactory
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
    }

    public function getPayment()
    {
        return $this->order->getPayment();
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface
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

            //request transaction details to confirm payment
            /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult $transactionDetailsResult */
            $transactionDetailsResult = $this->getPiRestApi()->transactionDetails(
                $this->getRequestData()->getTransactionId()
            );

            $this->_confirmPayment($transactionDetailsResult);

            //remove order pre-saved flag from checkout
            $this->checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", null);

        } else {
            $this->getResult()->setErrorMessage("Invalid 3D secure authentication.");
        }

        return $this->getResult();
    }

    /**
     * @param \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface $response
     * @throws \Magento\Framework\Validator\Exception
     */
    private function _confirmPayment(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface $response)
    {

        if ($response->getStatusCode() == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {
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

                //invoice
                $payment->getMethodInstance()->markAsInitialized();
                $this->order->place()->save();

                //send email
                $this->getCheckoutHelper()->sendOrderEmail($this->order);

                /*$transaction = $this->transactionFactory->create();
                $transaction->setOrderPaymentObject($payment);
                $transaction->setTxnId($this->getPayResult()->getTransactionId());
                $transaction->setOrderId($this->order->getEntityId());
                $transaction->setTxnType(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
                $transaction->setPaymentId($payment->getId());
                $transaction->setIsClosed(true);
                $transaction->save();*/

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
                throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order'));
            }
        } else {
            throw new \Magento\Framework\Validator\Exception(__('Invalid Sage Pay response'));
        }
    }
}