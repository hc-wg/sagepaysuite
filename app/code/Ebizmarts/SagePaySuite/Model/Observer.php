<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

/**
 * Ebizmarts SagePaySuite Observer
 */
namespace Ebizmarts\SagePaySuite\Model;

class Observer
{
    /**
     * before payment save
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function beforeOrderPaymentSave(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
//        $payment = $observer->getEvent()->getPayment();
//        $instructionMethods = [
//            Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE,
//            Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE
//        ];
//        if (in_array($payment->getMethod(), $instructionMethods)) {
//            $payment->setAdditionalInformation(
//                'instructions',
//                $payment->getMethodInstance()->getInstructions()
//            );
//        } elseif ($payment->getMethod() === Checkmo::PAYMENT_METHOD_CHECKMO_CODE) {
//            $payment->setAdditionalInformation(
//                'payable_to',
//                $payment->getMethodInstance()->getPayableTo()
//            );
//            $payment->setAdditionalInformation(
//                'mailing_address',
//                $payment->getMethodInstance()->getMailingAddress()
//            );
//        }

//        $this->_logger->addDebug('some text or variable');

    }
}
