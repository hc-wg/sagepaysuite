<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Api;


class Transaction extends \Ebizmarts\SagePaySuite\Model\Api\Reporting
{

    public function getTransactionDetails($vpstxid) {

        $params = '<vpstxid>' . $vpstxid . '</vpstxid>';

        $xml          = $this->_createXml('getTransactionDetail', $params);
        $api_response = $this->_executeRequest($xml);

        return $this->_handleApiErrors($api_response);
    }

}
