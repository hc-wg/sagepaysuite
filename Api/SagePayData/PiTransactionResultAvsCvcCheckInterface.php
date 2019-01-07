<?php
namespace Ebizmarts\SagePaySuite\Api\SagePayData;

/**
 * Interface PiTransactionResultAvsCvcCheckInterface
 *
 * @package Ebizmarts\SagePaySuite\Api\SagePayData
 */
interface PiTransactionResultAvsCvcCheckInterface
{
    const STATUS        = 'status';
    const ADDRESS       = 'address';
    const POSTAL_CODE   = 'postal_code';
    const SECURITY_CODE = 'security_code';

    /**
     * @param string $status
     * @return void
     */
    public function setStatus($status);

    public function getStatus();

    /**
     * @param string $addressStatus
     * @return void
     */
    public function setAddress($addressStatus);

    public function getAddress();

    /**
     * @param string $postalCodeStatus
     * @return void
     */
    public function setPostalCode($postalCodeStatus);

    public function getPostalCode();

    /**
     * @param string $securityCodeStatus
     * @return void
     */
    public function setSecurityCode($securityCodeStatus);

    public function getSecurityCode();
}
