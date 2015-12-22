<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Logger;

class ServerRequest extends \Magento\Framework\Logger\Handler\Base
{

    /**
     * File name
     * @var string
     */

    protected $fileName = '/var/log/SagePaySuite/SERVER_Request.log';

    public function isHandling(array $record)
    {
        return $record['level'] == \Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_SERVER_REQUEST;
    }

}