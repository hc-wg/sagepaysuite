<?php

namespace Ebizmarts\SagePaySuite\Model\Api;

use Ebizmarts\SagePaySuite\Model\Config;

interface PaymentOperations
{
    const DEFERRED_AWAITING_RELEASE = 14;
    const SUCCESSFULLY_AUTHORISED   = 16;

    public function captureDeferredTransaction($transactionId, $amount);
    public function refundTransaction($transactionId, $amount, $orderId);
    public function authorizeTransaction($transactionId, $amount, $orderId);
    public function repeatTransaction($vpstxid, $quote_data, $paymentAction = Config::ACTION_REPEAT);
}