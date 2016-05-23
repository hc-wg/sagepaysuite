<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block;

/**
 * Sage Pay generic payment info block
 * Uses default template
 */
class Info extends \Magento\Payment\Block\Info\Cc
{
    /**
     * @param null $transport
     * @return mixed
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();

        $info = array();
        if ($payment->getCcExpMonth()) {
            $info["Credit Card Expiration"] = $payment->getCcExpMonth() . "/" . $payment->getCcExpYear();
        }

        return $transport->addData($info);
    }
}
