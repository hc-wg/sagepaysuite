<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Ebizmarts\SagePaySuite\Model\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ProcessableException extends LocalizedException
{
    /**#@+
     * Error code returned by SagePay
     */
    const API_INVALID_IP = 4020;
    /**#@-*/

    /**
     * Constructor
     *
     * @param \Magento\Framework\Phrase $phrase
     * @param \Exception $cause
     * @param int $code
     */
    public function __construct(Phrase $phrase, \Exception $cause = null, $code = 0)
    {
        parent::__construct($phrase, $cause);
        $this->code = $code;
    }

    /**
     * Get error message which can be displayed to website user
     *
     * @return \Magento\Framework\Phrase
     */
    public function getUserMessage()
    {
        switch ($this->getCode()) {
            case self::API_INVALID_IP:
                $message = __(
                    'Information received from an Invalid IP address.'
                );
                break;
            default:
                $message = __($this->getMessage());
                break;
        }
        return $message;
    }
}
