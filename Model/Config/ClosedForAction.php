<?php

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

    public function __construct($paymentAction)
    {
        $this->paymentAction = $paymentAction;
    }

    /**
     * @return array
     */
    public function getActionClosedForPaymentAction()
    {
        switch ($this->paymentAction) {
            case Config::ACTION_PAYMENT:
                $action = Transaction::TYPE_CAPTURE;
                $closed = true;
                break;
            case Config::ACTION_DEFER:
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
