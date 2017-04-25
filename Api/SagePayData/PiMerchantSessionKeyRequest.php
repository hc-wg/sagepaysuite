<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/26/17
 * Time: 1:54 PM
 */

namespace Ebizmarts\SagePaySuite\Api\SagePayData;

class PiMerchantSessionKeyRequest extends \Magento\Framework\Api\AbstractExtensibleObject implements PiMerchantSessionKeyRequestInterface
{
    /**
     * @inheritDoc
     */
    public function getVendorName()
    {
        return $this->_get(self::VENDOR_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setVendorName($name)
    {
        $this->setData(self::VENDOR_NAME, $name);
    }
}
