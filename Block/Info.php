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

        //only backend details
//        if ($this->_appState->getAreaCode() === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE)
//        {
//            if ($payment->getAdditionalInformation("moto")) {
//                $info["Source"] = "Backend Order";
//            }else{
//                $info["Source"] = "Frontend Order";
//            }
//        }

        return $transport->addData($info);
    }
}
