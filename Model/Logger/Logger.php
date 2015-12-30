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
    const LOG_SERVER_REQUEST = 'SERVER_Request';
    const LOG_SERVER_NOTIFY = 'SERVER_Notify';

    const LOG_PI_REQUEST = 'PI_Request';

    protected static $levels = array(
        self::LOG_SERVER_REQUEST => 'SERVER_Request',
        self::LOG_SERVER_NOTIFY => 'SERVER_Notify',
        self::LOG_PI_REQUEST => 'PI_Request'
    );

    public function SageLog($logType, $message)
    {

        try {

            if(is_null($message)){
                $message = "NULL";
            }

            if (is_array($message)) {
                $message = json_encode($message,JSON_PRETTY_PRINT);
            }

            if (is_object($message)) {
                $message = json_encode($message,JSON_PRETTY_PRINT);
            }

            $message = (string)$message;

        } catch (\Exception $e) {
            $message = "INVALID MESSAGE: " . gettype($message);
        }

        $message .= "\n\n";

        return $this->addRecord($logType, $message, array());
    }

}