<?php

namespace Ebizmarts\SagePaySuite\Model\Config;

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
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT:
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                $closed = true;
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER:
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                $closed = false;
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE:
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                $closed = false;
                break;
            default:
                $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                $closed = true;
                break;
        }
        return [$action, $closed];
    }
}
