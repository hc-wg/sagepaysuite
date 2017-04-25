<?php

namespace Ebizmarts\SagePaySuite\Api\SagePayData;

class PiMerchantSessionKeyResponse extends \Magento\Framework\Api\AbstractExtensibleObject implements PiMerchantSessionKeyResponseInterface
{
    /**
     * @inheritDoc
     */
    public function getMerchantSessionKey()
    {
        return $this->_get(self::MERCHANT_SESSION_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setMerchantSessionKey($key)
    {
        $this->setData(self::MERCHANT_SESSION_KEY, $key);
    }

    /**
     * @inheritDoc
     */
    public function getExpiry()
    {
        return $this->_get(self::EXPIRY);
    }

    /**
     * @inheritDoc
     */
    public function setExpiry($dateTime)
    {
        $this->setData(self::EXPIRY, $dateTime);
    }
}
