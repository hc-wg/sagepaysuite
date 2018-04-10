<?php

declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Model\Config;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class ClosedForAction
 * @package Ebizmarts\SagePaySuite\Model\Config
 */
class ClosedForAction
{
    /** @var array */
    private $paymentAction;

    public function __construct(string $paymentAction = null)
    {
        $this->paymentAction = $paymentAction;
    }

    /**
     * @return array
     */
    public function getActionClosedForPaymentAction() : array
    {
        switch ($this->paymentAction) {
            case Config::ACTION_PAYMENT:
            case Config::ACTION_PAYMENT_PI:
                $action = Transaction::TYPE_CAPTURE;
                $closed = true;
                break;
            case Config::ACTION_DEFER:
            case Config::ACTION_DEFER_PI:
                $action = Transaction::TYPE_AUTH;
                $closed = false;
                break;
            case Config::ACTION_AUTHENTICATE:
                $action = Transaction::TYPE_AUTH;
                $closed = false;
                break;
            default:
                $action = Transaction::TYPE_CAPTURE;
                $closed = true;
                break;
        }
        return [$action, $closed];
    }
}
