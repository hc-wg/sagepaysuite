<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Ebizmarts\SagePaySuite\Model\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ApiException extends LocalizedException
{
    /**
     * Error code returned by SagePay
     */
    const VALID_VPSTXID_REQUIRED = 3002;
    const API_INVALID_IP = 4020;
    const INVALID_MERCHANT_AUTHENTICATION = 1002;


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
                    'Information received from an invalid IP address.'
                );
                break;
            case self::VALID_VPSTXID_REQUIRED:
                $message = __(
                    'Invalid transaction id.'
                );
                break;
            case self::INVALID_MERCHANT_AUTHENTICATION:
                $message = __(
                    'Invalid merchant authentication.'
                );
                break;
            default:
                $message = __($this->getMessage());
                break;
        }
        return $message;
    }
}
