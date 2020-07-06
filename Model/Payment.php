<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;

class Payment
{
    const ERROR_MESSAGE = "There was an error %1 Opayo transaction %2: %3";

    /** @var Api\Shared */
    private $sharedApi;

    /** @var \Ebizmarts\SagePaySuite\Model\Logger\Logger */
    private $logger;

    /** @var  */
    private $suiteHelper;

    /** @var \Ebizmarts\SagePaySuite\Model\Config */
    private $config;

    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Api\Shared $sharedApi,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $logger,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Config $config
    ) {
        $this->logger      = $logger;
        $this->sharedApi   = $sharedApi;
        $this->suiteHelper = $suiteHelper;
        $this->config      = $config;
    }

    /**
     * @param InfoInterface $payment
     * @param $amount
     * @return $this
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        try {
            $transactionId = "-1";
            $action        = "with";
            $order         = $payment->getOrder();

            if ($this->canCaptureAuthorizedTransaction($payment, $order)) {
                $transactionId = $payment->getParentTransactionId();

                $paymentAction = $this->getTransactionPaymentAction($payment);

                $result = [];
                if ($this->isDeferredOrRepeatDeferredAction($paymentAction)) {
                    $action = 'releasing';
                    if ($this->config->getCurrencyConfig() === CONFIG::CURRENCY_SWITCHER) {
                        $amount = $this->calculateAmount($amount, $order);
                    }
                    $result = $this->sharedApi->captureDeferredTransaction($transactionId, $amount, $order);
                } elseif ($this->isAuthenticateAction($paymentAction)) {
                    $action = 'authorizing';
                    if ($this->config->getCurrencyConfig() === CONFIG::CURRENCY_SWITCHER) {
                        $amount = $this->calculateAmount($amount, $order);
                    }
                    $result = $this->sharedApi->authorizeTransaction($transactionId, $amount, $order);
                }

                if (is_array($result) && array_key_exists('data', $result)) {
                    $this->addAdditionalInformationToTransaction($payment, $result);

                    if (array_key_exists('VPSTxId', $result['data'])) {
                        $payment->setTransactionId($this->suiteHelper->removeCurlyBraces($result['data']['VPSTxId']));
                    }
                    $payment->setParentTransactionId($payment->getParentTransactionId());
                } else {
                    $payment->setTransactonId($payment->getLastTransId());
                    $payment->setParentTransactionId($payment->getLastTransId());
                }

                //TODO: También probar AUTHENTICATE.
            }
        } catch (ApiException $apiException) {
            $this->logger->logException($apiException);
            throw new LocalizedException(__(self::ERROR_MESSAGE, $action, $transactionId, $apiException->getUserMessage()));
        } catch (\Exception $e) {
            $this->logger->logException($e);
            throw new LocalizedException(__(self::ERROR_MESSAGE, $action, $transactionId, $e->getMessage()));
        }

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $transactionId = $this->suiteHelper->clearTransactionId($payment->getParentTransactionId());

        try {
            if ($this->config->getCurrencyConfig() === CONFIG::CURRENCY_SWITCHER) {
                $amount = $this->calculateAmount($amount, $order);
            }
            $this->tryRefund($payment, $transactionId, $amount);
        } catch (ApiException $apiException) {
            $this->logger->logException($apiException);
            throw new LocalizedException(
                __(self::ERROR_MESSAGE, "refunding", $transactionId, $apiException->getUserMessage())
            );
        } catch (\Exception $e) {
            $this->logger->logException($e);
            throw new LocalizedException(__(self::ERROR_MESSAGE, "refunding", $transactionId, $e->getMessage()));
        }

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param $transactionId
     * @param $amount
     * @return mixed
     */
    private function tryRefund(InfoInterface $payment, $transactionId, $amount)
    {
        $result = $this->sharedApi->refundTransaction($transactionId, $amount, $payment->getOrder());

        $this->addAdditionalInformationToTransaction($payment, $result);

        $payment->setIsTransactionClosed(1);
        $payment->setShouldCloseParentTransaction(1);

        return $transactionId;
    }

    /**
     * @param $payment
     * @param $paymentAction
     * @param $stateObject
     */
    public function setOrderStateAndStatus($payment, $paymentAction, $stateObject)
    {
        if ($paymentAction == 'PAYMENT') {
            $this->setPendingPaymentState($stateObject);
        } elseif ($paymentAction == 'DEFERRED' || $paymentAction == 'AUTHENTICATE') {
            if ($payment->getLastTransId() !== null) {
                $stateObject->setState(\Magento\Sales\Model\Order::STATE_NEW);
                $stateObject->setStatus('pending');
            } else {
                $this->setPendingPaymentState($stateObject);
            }
        }
    }

    /**
     * @param $stateObject
     */
    private function setPendingPaymentState($stateObject)
    {
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
    }

    /**
     * @param InfoInterface $payment
     * @param $result
     */
    private function addAdditionalInformationToTransaction(InfoInterface $payment, $result)
    {
        if (is_array($result) && array_key_exists('data', $result)) {
            foreach ($result['data'] as $name => $value) {
                $payment->setTransactionAdditionalInfo($name, $value);
            }
        }
    }

    /**
     * @param $paymentAction
     * @return bool
     */
    private function isDeferredOrRepeatDeferredAction($paymentAction)
    {
        return $paymentAction === Config::ACTION_DEFER || $paymentAction === Config::ACTION_REPEAT_DEFERRED;
    }

    /**
     * @param $paymentAction
     * @return bool
     */
    private function isAuthenticateAction($paymentAction)
    {
        return $paymentAction == Config::ACTION_AUTHENTICATE;
    }

    /**
     * @param InfoInterface $payment
     * @param $order
     * @return bool
     */
    private function canCaptureAuthorizedTransaction(InfoInterface $payment, $order)
    {
        return $payment->getLastTransId() && $order->getState() != \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
    }

    /**
     * @param InfoInterface $payment
     * @return mixed|null|string
     */
    private function getTransactionPaymentAction(InfoInterface $payment)
    {
        $paymentAction = $this->config->getSagepayPaymentAction();
        if ($payment->getAdditionalInformation('paymentAction')) {
            $paymentAction = $payment->getAdditionalInformation('paymentAction');
        }

        return $paymentAction;
    }

    /**
     * @param $amount
     * @param $order
     * @return float|int
     */
    public function calculateAmount($amount, $order)
    {
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $baseCurrencyCode = $order->getBaseCurrencyCode();

        if ($baseCurrencyCode !== $orderCurrencyCode) {
            $rate = $order->getBaseToOrderRate();
            $currencySwitcherAmount = $amount * $rate;
            $amount = $currencySwitcherAmount;
        }
        return $amount;
    }
}
