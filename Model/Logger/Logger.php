<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Logger;

use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\Config;

class Logger extends \Monolog\Logger
{

    /**
     * SagePaySuite log files
     */
    const LOG_REQUEST   = 'Request';
    const LOG_CRON      = 'Cron';
    const LOG_EXCEPTION = 'Exception';
    const LOG_DEBUG     = 'Debug';

    // @codingStandardsIgnoreStart
    protected static $levels = [
        self::LOG_REQUEST   => 'Request',
        self::LOG_CRON      => 'Cron',
        self::LOG_EXCEPTION => 'Exception',
        self::LOG_DEBUG     => 'Debug'
    ];
    // @codingStandardsIgnoreEnd

    /** @var Config */
    private $config;

    /** @var Data */
    private $suiteHelper;

    /**
     * Logger constructor.
     * @param Config $config
     * @param Data $suiteHelper
     * @param string $name
     * @param array $handlers
     */
    public function __construct(
        Config $config,
        Data $suiteHelper,
        string $name,
        array $handlers
    ) {
        parent::__construct($name, $handlers);
        $this->config      = $config;
        $this->suiteHelper = $suiteHelper;
    }

    /**
     * @param $logType
     * @param $message
     * @param array $context
     * @return bool
     */
    public function sageLog($logType, $message, $context = [])
    {
        $message = $this->messageForLog($message);
        $message .= "\r\n";

        return $this->addRecord($logType, $message, $context);
    }

    public function logException($exception, $context = [])
    {
        $message = $exception->getMessage();
        $message .= "\n";
        $message .= $exception->getTraceAsString();
        $message .= "\r\n\r\n";

        return $this->addRecord(self::LOG_EXCEPTION, $message, $context);
    }

    /**
     * @param string|array $message
     * @param array $context
     * @return bool
     */
    public function debugLog($message, $context = [])
    {
        $recordSaved = false;
        if ($this->config->getDebugMode()) {
            $recordSaved = $this->sageLog(self::LOG_DEBUG, $message, $context);
        }
        return $recordSaved;
    }

    /**
     * @param $message
     * @return string
     */
    private function messageForLog($message)
    {
        if ($message === null) {
            $message = "NULL";
        }

        if (is_array($message)) {
            $message = $this->suiteHelper->removePersonalInformation($message);
            $message = json_encode($message, JSON_PRETTY_PRINT);
        }

        if (is_object($message)) {
            $message = $this->suiteHelper->removePersonalInformationObject($message);
            $message = json_encode($message, JSON_PRETTY_PRINT);
        }

        if (!empty(json_last_error())) {
            $message = json_last_error_msg();
        }

        $message = (string)$message;

        return $message;
    }

    /**
     * @param string $paymentMethod
     * @param string $incrementId
     * @param int $cartId
     */
    public function orderStartLog($paymentMethod, $incrementId, $cartId)
    {
        $message = "\n";
        $message .= '---------- ';
        $message .= "Starting order with " . $paymentMethod . ": Order: " . $incrementId . " - Cart: " . $cartId;
        $message .= ' ----------';
        $this->sageLog(self::LOG_REQUEST, $message);
        $this->debugLog($message);
    }

    /**
     * @param $vpstxid
     * @param $incrementId
     * @param $cartId
     */
    public function orderEndLog($incrementId, $cartId, $vpstxid = null)
    {
        $message = "\n";
        $message .= '---------- ';
        $message .= "End of Order " . $incrementId;
        $message .= " - Cart: " . $cartId;
        if (!empty($vpstxid)) {
            $message .= " - VPSTxId: " . $vpstxid;
        }
        $message .= ' ----------';
        $this->sageLog(self::LOG_REQUEST, $message);
        $this->debugLog($message);
    }
}
