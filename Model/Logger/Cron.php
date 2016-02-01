<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Logger;

class Cron extends \Magento\Framework\Logger\Handler\Base
{

    /**
     * File name
     * @var string
     */

    protected $fileName = '/var/log/SagePaySuite/Cron.log';

    public function isHandling(array $record)
    {
        return $record['level'] == \Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON;
    }

}