<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Block;

/**
 * SagePay payment info block
 * Uses default templates
 */
class Info extends \Magento\Payment\Block\Info\Cc
{

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        array $data = []
    )
    {
        parent::__construct($context, $paymentConfig, $data);
    }

    /**
     * Prepare SagePay-specific payment information
     *
     * @param \Magento\Framework\Object|array|null $transport
     * @return \Magento\Framework\Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();

        $info = array();
        if ($payment->getCcExpMonth()) {
            $info["Card Expiration Date"] = $payment->getCcExpMonth() . "/" . $payment->getCcExpYear();
        }

        //only backend details
        if ($this->_appState->getAreaCode() === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE){
            if ($payment->getAdditionalInformation("vendorTxCode")) {
                $info["VendorTxCode"] = $payment->getAdditionalInformation("vendorTxCode");
            }
            if ($payment->getLastTransId()) {
                $info["VPSTxId"] = $payment->getLastTransId();
            }
            if ($payment->getAdditionalInformation("statusDetail")) {
                $info["Status"] = $payment->getAdditionalInformation("statusDetail");
            }
        }

        return $transport->addData($info);
    }
}
