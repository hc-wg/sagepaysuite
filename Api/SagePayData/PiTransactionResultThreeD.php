<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/25/17
 * Time: 6:20 PM
 */

namespace Ebizmarts\SagePaySuite\Api\SagePayData;

class PiTransactionResultThreeD extends \Magento\Framework\Api\AbstractExtensibleObject implements \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeDInterface
{
    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
    }
}
