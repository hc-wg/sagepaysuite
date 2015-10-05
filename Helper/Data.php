<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param Number $order_id
     * @param String $action
     */
    public function generateVendorTxCode($order_id, $action=\Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT){

        $prefix = "";
        switch($action){
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND:
                $prefix = "R";
        }

        return substr($prefix . $order_id . "-" . date('Y-m-d-His') . time(), 0, 40);
    }
}
