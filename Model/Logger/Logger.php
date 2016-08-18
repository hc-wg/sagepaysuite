<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Ebizmarts\SagePaySuite\Model\Logger;

class Logger extends \Monolog\Logger
{

    /**
     * SagePaySuite log files
     */
    const LOG_REQUEST = 'Request';
    const LOG_CRON = 'Cron';
    const LOG_EXCEPTION = 'Exception';

    protected static $levels = [
        self::LOG_REQUEST => 'Request',
        self::LOG_CRON => 'Cron',
        self::LOG_EXCEPTION => 'Exception'
    ];

    public function sageLog($logType, $message)
    {

        try {
            if (is_null($message)) {
                $message = "NULL";
            }

            if (is_array($message)) {
                $message = json_encode($message, JSON_PRETTY_PRINT);
            }

            if (is_object($message)) {
                $message = json_encode($message, JSON_PRETTY_PRINT);
            }

            if (!empty(json_last_error())) {
                $message = (string)json_last_error();
            }

            $message = (string)$message;
        } catch (\Exception $e) {
            $message = "INVALID MESSAGE";
        }

        $message .= "\r\n";

        return $this->addRecord($logType, $message, []);
    }

    public function logException($exception)
    {
        $message = $exception->getMessage();
        $message .= "\n";
        $message .= $exception->getTraceAsString();
        $message .= "\r\n\r\n";

        return $this->addRecord(self::LOG_EXCEPTION, $message, []);
    }
}
