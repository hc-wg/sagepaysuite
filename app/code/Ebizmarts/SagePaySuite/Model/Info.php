<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Ebizmarts\SagePaySuite\Model;

/**
 * SagePay payment information model
 *
 * Aware of all SagePay payment methods
 * Collects and provides access to SagePay-specific payment data
 * Provides business logic information about payment flow
 */
class Info
{

    /**
     * All available payment info getter
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param bool $labelValuesOnly
     * @return array
     */
    public function getPaymentInfo(\Magento\Payment\Model\InfoInterface $payment, $labelValuesOnly = false)
    {
        $result = array();

        $result[\Ebizmarts\SagePaySuite\Model\Config::VAR_VendorTxCode] = $payment->getAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_VendorTxCode);
        $result[\Ebizmarts\SagePaySuite\Model\Config::VAR_VPSTxId] = $payment->getAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_VPSTxId);
        $result["Status"] = $payment->getAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_StatusDetail);
        $result[\Ebizmarts\SagePaySuite\Model\Config::VAR_AVSCV2] = $payment->getAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_AVSCV2);
        $result["3D Secure Status"] = $payment->getAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_3DSecureStatus);
        $result["Bank Auth Code"] = $payment->getAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_BankAuthCode);
        $result["Card Type"] = $payment->getCcType();
        $result["Card Expiration Date"] = $payment->getCcExpMonth();

        return $result;
    }

    public function getPublicPaymentInfo(\Magento\Payment\Model\InfoInterface $payment, $labelValuesOnly = false)
    {
        $result = array();

        $result[\Ebizmarts\SagePaySuite\Model\Config::VAR_VPSTxId] = $payment->getAdditionalInformation(\Ebizmarts\SagePaySuite\Model\Config::VAR_VPSTxId);
        $result["Card Type"] = $payment->getCcType();
        $result["Card Expiration Date"] = $payment->getCcExpMonth();

        return $result;
    }


}
