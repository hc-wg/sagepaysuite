<?php

namespace Ebizmarts\SagePaySuite\Model\Api;

use Ebizmarts\SagePaySuite\Model\Config;

interface PaymentOperations
{
    const DEFERRED_AWAITING_RELEASE = 14;
    const SUCCESSFULLY_AUTHORISED   = 16;

    public function captureDeferredTransaction($transactionId, $amount);

    /**
     * @param $transactionId
     * @param $amount
     * @param \Magento\Sales\Api\Data\OrderInterface $order.
     * @return mixed
     */
    public function refundTransaction($transactionId, $amount, \Magento\Sales\Api\Data\OrderInterface $order);

    /**
     * @param $transactionId
     * @param $amount
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return mixed
     */
    public function authorizeTransaction($transactionId, $amount, \Magento\Sales\Api\Data\OrderInterface $order);

    public function repeatTransaction($vpstxid, $quote_data, $paymentAction = Config::ACTION_REPEAT);
}