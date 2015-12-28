<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Model\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class BeforeQuoteSubmit implements ObserverInterface
{

    /**
     *
     */
    public function __construct(
    ) {

    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
//        $order = $observer->getOrder();
//        $quote = $observer->getQuote();
//        $payment = $order->getPayment();
//
//        if($payment->getMethod() == \Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER){
//            //set payment action for pending payment status
//            $payment->getMethodInstance()->setPaymentAction(\Magento\Payment\Model\Method\AbstractMethod::ACTION_ORDER);
//        }
//
//
//        return $this;
    }
}
