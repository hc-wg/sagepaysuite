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
    const VALID_VALUE_REQUIRED = '3002';
    const API_INVALID_IP = '4020';
    const INVALID_MERCHANT_AUTHENTICATION = '1002';
    const INVALID_USER_AUTH = '0008';
    const INVALID_TRANSACTION_STATE = '5004';


    /**
     * Constructor
     *
     * @param \Magento\Framework\Phrase $phrase
     * @param Magento\Framework\Exception\LocalizedException $cause
     * @param int $code
     */
    public function __construct(Phrase $phrase, LocalizedException $cause = null, $code = 0)
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
            case self::VALID_VALUE_REQUIRED:
                if(strpos($this->getMessage(),"vpstxid") !== FALSE){
                    $message = __('Transaction NOT found / Invalid transaction Id.');
                }else{
                    $message = __($this->getMessage());
                }
                break;
            case self::INVALID_MERCHANT_AUTHENTICATION:
                $message = __(
                    'Invalid merchant authentication.'
                );
                break;
            case self::INVALID_USER_AUTH:
                $message = __(
                    'Your Sage Pay API user/password is invalid or it might be locked out.'
                );
                break;
            default:
                $message = __($this->getMessage());
                break;
        }
        return $message;
    }

}
