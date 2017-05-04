<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/25/17
 * Time: 4:01 PM
 */

namespace Ebizmarts\SagePaySuite\Api\SagePayData;

class PiTransactionResultPaymentMethod extends \Magento\Framework\Api\AbstractExtensibleObject implements \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethodInterface
{
    /**
     * @inheritDoc
     */
    public function getCard()
    {
        return $this->_get(self::CARD);
    }

    /**
     * @inheritDoc
     */
    public function setCard($card)
    {
        $this->setData(self::CARD, $card);
    }
}
