<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/25/17
 * Time: 3:48 PM
 */

namespace Ebizmarts\SagePaySuite\Api\SagePayData;

use Magento\Framework\Api\AbstractExtensibleObject;

class PiTransactionResultAvsCvcCheck extends AbstractExtensibleObject implements PiTransactionResultAvsCvcCheckInterface
{

    /**
     * @param string $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
    }

    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * @param string $addressStatus
     * @return void
     */
    public function setAddress($addressStatus)
    {
        $this->setData(self::ADDRESS, $addressStatus);
    }

    public function getAddress()
    {
        return $this->_get(self::ADDRESS);
    }

    /**
     * @param string $postalCodeStatus
     * @return void
     */
    public function setPostalCode($postalCodeStatus)
    {
        $this->setData(self::POSTAL_CODE, $postalCodeStatus);
    }

    public function getPostalCode()
    {
        return $this->_get(self::POSTAL_CODE);
    }

    /**
     * @param string $securityCodeStatus
     * @return void
     */
    public function setSecurityCode($securityCodeStatus)
    {
        $this->setData(self::SECURITY_CODE, $securityCodeStatus);
    }

    public function getSecurityCode()
    {
        return $this->_get(self::SECURITY_CODE);
    }
}
