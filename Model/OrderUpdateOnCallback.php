<?php

namespace Ebizmarts\SagePaySuite\Model;


use Braintree\Exception;

class OrderUpdateOnCallback
{
    /** @var \Magento\Sales\Model\Order */
    private $order;

    /** @var \Ebizmarts\SagePaySuite\Model\Config */
    private $config;

    /** @var \Magento\Sales\Model\Order\Email\Sender\OrderSender */
    private $orderEmailSender;

    /** @var Config\ClosedForActionFactory */
    private $actionFactory;

    /** @var \Magento\Sales\Model\Order\Payment\TransactionFactory */
    private $transactionFactory;

    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender,
        \Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory $actionFactory,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
    )
    {
        $this->config = $config;
        $this->orderEmailSender = $orderEmailSender;
        $this->actionFactory = $actionFactory;
        $this->transactionFactory = $transactionFactory;
    }

    public function setOrder(\Magento\Sales\Model\Order $order)
    {
        $this->order = $order;
    }

    public function confirmPayment($transactionId)
    {
        if ($this->order === null) {
            throw new Exception("Invalid order. Cant confirm payment.");
        }

        $payment = $this->order->getPayment();

        $sagePayPaymentAction = $this->config->getSagepayPaymentAction();
        if ($sagePayPaymentAction != 'DEFERRED' && $sagePayPaymentAction != 'AUTHENTICATE') {
            $payment->getMethodInstance()->markAsInitialized();
        }

        $this->order->place()->save();

        if ((bool)$payment->getAdditionalInformation('euroPayment') !== true) {
            //don't send email if EURO PAYMENT as it was already sent
            $this->orderEmailSender->send($this->order);
        }

        /** @var \Ebizmarts\SagePaySuite\Model\Config\ClosedForAction $actionClosed */
        $actionClosed = $this->actionFactory->create(['paymentAction' => $sagePayPaymentAction]);
        list($action, $closed) = $actionClosed->getActionClosedForPaymentAction();

        $transaction = $this->transactionFactory->create();
        $transaction->setOrderPaymentObject($payment);
        $transaction->setTxnId($transactionId);
        $transaction->setOrderId($this->order->getEntityId());
        $transaction->setTxnType($action);
        $transaction->setPaymentId($payment->getId());
        $transaction->setIsClosed($closed);
        $transaction->save();

        //update invoice transaction id
        $this->order->getInvoiceCollection()
            ->setDataToAll('transaction_id', $payment->getLastTransId())
            ->save();
    }
}