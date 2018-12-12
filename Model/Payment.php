<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Api\PaymentOperations;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

class Payment
{
    const ERROR_MESSAGE = "There was an error %1 Sage Pay transaction %2: %3";

    /** @var Api\Shared|\Ebizmarts\SagePaySuite\Model\Api\Pi */
    private $api;

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
        $this->api         = $sharedApi;
        $this->suiteHelper = $suiteHelper;
        $this->config      = $config;
    }

    public function setApi(PaymentOperations $apiInstance)
    {
        $this->api = $apiInstance;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return $this
     * @throws LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
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
                    $result = $this->api->captureDeferredTransaction($transactionId, $amount);
                } elseif ($this->isAuthenticateAction($paymentAction)) {
                    $action = 'authorizing';
                    $result = $this->api->authorizeTransaction($transactionId, $amount, $order);
                }

                if (\is_array($result) && array_key_exists('data', $result)) {
                    $this->addAdditionalInformationToTransaction($payment, $result);

                    if (array_key_exists('VPSTxId', $result['data'])) {
                        $payment->setTransactionId($this->suiteHelper->removeCurlyBraces($result['data']['VPSTxId']));
                    }
                    $payment->setParentTransactionId($payment->getParentTransactionId());
                } elseif ($result instanceof PiTransactionResultInterface) { //Pi repeat
                    /** @var $result PiTransactionResultInterface */
                    $payment->setTransactionId($result->getTransactionId());
                    $payment->setParentTransactionId($payment->getParentTransactionId());
                }
            }
        } catch (ApiException $apiException) {
            $this->logger->logException($apiException);
            throw new LocalizedException(
                __(
                    self::ERROR_MESSAGE,
                    $action,
                    $transactionId,
                    $apiException->getUserMessage()
                )
            );
        } catch (\Exception $e) {
            $this->logger->logException($e);
            throw new LocalizedException(__(self::ERROR_MESSAGE, $action, $transactionId, $e->getMessage()));
        }

        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionId = $this->suiteHelper->clearTransactionId($payment->getParentTransactionId());
        try {
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
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $transactionId
     * @param $amount
     * @return mixed
     */
    private function tryRefund(\Magento\Payment\Model\InfoInterface $payment, $transactionId, $amount)
    {
        $result = $this->api->refundTransaction($transactionId, $amount, $payment->getOrder());

        $this->addAdditionalInformationToTransaction($payment, $result);

        $payment->setIsTransactionClosed(1);
        $payment->setShouldCloseParentTransaction(1);

        return $transactionId;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     */
    public function setOrderStateAndStatus($payment, string $paymentAction, $stateObject)
    {
        if ($paymentAction == 'PAYMENT') {
            $this->setPendingPaymentState($stateObject);
        } elseif ($paymentAction == 'DEFERRED' || $paymentAction == 'AUTHENTICATE') {
            if ($payment->getLastTransId() !== null) {
                $stateObject->setState(Order::STATE_NEW);
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
        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $result
     */
    private function addAdditionalInformationToTransaction(\Magento\Payment\Model\InfoInterface $payment, $result)
    {
        if (\is_array($result) && array_key_exists('data', $result)) {
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
        return $paymentAction === Config::ACTION_DEFER ||
            $paymentAction === Config::ACTION_REPEAT_DEFERRED ||
            $paymentAction === Config::ACTION_DEFER_PI;
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
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $order
     * @return bool
     */
    private function canCaptureAuthorizedTransaction(\Magento\Payment\Model\InfoInterface $payment, $order)
    {
        return $payment->getLastTransId() && $order->getState() != Order::STATE_PENDING_PAYMENT;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return mixed|null|string
     */
    private function getTransactionPaymentAction(\Magento\Payment\Model\InfoInterface $payment)
    {
        $paymentAction = $this->config->getSagepayPaymentAction();
        if ($payment->getAdditionalInformation('paymentAction')) {
            $paymentAction = $payment->getAdditionalInformation('paymentAction');
        }

        return $paymentAction;
    }
}
