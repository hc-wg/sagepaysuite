<?php

namespace Ebizmarts\SagePaySuite\Model;

class Payment
{
    /** @var Api\Shared */
    private $sharedApi;

    /** @var \Ebizmarts\SagePaySuite\Model\Logger\Logger */
    private $logger;

    /** @var  */
    private $suiteHelper;

    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Api\Shared $sharedApi,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $logger,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
    ) {
        $this->logger      = $logger;
        $this->sharedApi   = $sharedApi;
        $this->suiteHelper = $suiteHelper;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $transactionId = "-1";
            $action        = "with";
            $order         = $payment->getOrder();

            if ($payment->getLastTransId() && $order->getState() != \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
                $transactionId = $payment->getLastTransId();

                $paymentAction = $this->_config->getSagepayPaymentAction();
                if ($payment->getAdditionalInformation('paymentAction')) {
                    $paymentAction = $payment->getAdditionalInformation('paymentAction');
                }

                if ($paymentAction == \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER
                    || $paymentAction == \Ebizmarts\SagePaySuite\Model\Config::ACTION_REPEAT_DEFERRED
                ) {
                    $action = 'releasing';
                    $this->sharedApi->releaseTransaction($transactionId, $amount);
                } elseif ($paymentAction == \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE) {
                    $action = 'authorizing';
                    $this->sharedApi->authorizeTransaction($transactionId, $amount, $order->getIncrementId());
                }

                $payment->setIsTransactionClosed(1);
            }
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->logger->critical($apiException);
            throw new \Magento\Framework\Exception\LocalizedException(__("There was an error %1 Sage Pay transaction %2: %3", $action, $transactionId, $apiException->getUserMessage()));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(__("There was an error %1 Sage Pay transaction %2: %3", $action, $transactionId, $e->getMessage()));
        }

        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $transactionId = $this->suiteHelper->clearTransactionId($payment->getLastTransId());
            $order         = $payment->getOrder();

            $this->sharedApi->refundTransaction($transactionId, $amount, $order->getIncrementId());

            $payment->setIsTransactionClosed(1);
            $payment->setShouldCloseParentTransaction(1);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->logger->critical($apiException);
            throw new \Magento\Framework\Exception\LocalizedException(__("There was an error refunding Sage Pay transaction %1: %2", $transactionId, $apiException->getUserMessage()));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(__("There was an error refunding Sage Pay transaction %1: %2", $transactionId, $e->getMessage()));
        }

        return $this;
    }
}
