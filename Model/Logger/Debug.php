<?php

namespace Ebizmarts\SagePaySuite\Model\Logger;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Logger\Handler\Base;

class Debug extends Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/SagePaySuite/Debug.log'; // @codingStandardsIgnoreLine

    /**
     * @param array $record
     * @return bool
     */
    public function isHandling(array $record)
    {
        return $record['level'] == Logger::LOG_DEBUG;
    }
}
