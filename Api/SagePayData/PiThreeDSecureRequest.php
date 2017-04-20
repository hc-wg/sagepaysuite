<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/26/17
 * Time: 3:20 PM
 */

namespace Ebizmarts\SagePaySuite\Api\SagePayData;

class PiThreeDSecureRequest extends \Magento\Framework\Api\AbstractExtensibleObject implements PiThreeDSecureRequestInterface
{
    /**
     * @inheritDoc
     */
    public function getParEs()
    {
        return $this->_get(self::PAR_ES);
    }

    /**
     * @inheritDoc
     */
    public function setParEs($message)
    {
        $this->setData(self::PAR_ES, $message);
    }
}
