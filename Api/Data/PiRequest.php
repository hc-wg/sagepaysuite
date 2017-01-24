<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/24/17
 * Time: 3:14 PM
 */

namespace Ebizmarts\SagePaySuite\Api\Data;


class PiRequest extends \Magento\Framework\Api\AbstractExtensibleObject implements PiRequestInterface
{

    /**
     * @inheritDoc
     */
    public function getCardIdentifier()
    {
        return $this->_get(self::CARD_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCardIdentifier($cardId)
    {
        $this->setData(self::CARD_ID, $cardId);
    }

    /**
     * @inheritDoc
     */
    public function getMerchantSessionKey()
    {
        return $this->_get(self::MSK);
    }

    /**
     * @inheritDoc
     */
    public function setMerchantSessionKey($msk)
    {
        $this->setData(self::MSK, $msk);
    }

    /**
     * @inheritDoc
     */
    public function getCcLastFour()
    {
        return $this->_get(self::CARD_LAST_FOUR);
    }

    /**
     * @inheritDoc
     */
    public function setCcLastFour($lastFour)
    {
        $this->setData(self::CARD_LAST_FOUR, $lastFour);
    }

    /**
     * @inheritDoc
     */
    public function getCcExpMonth()
    {
        return $this->_get(self::CARD_EXP_MONTH);
    }

    /**
     * @inheritDoc
     */
    public function setCcExpMonth($expiryMonth)
    {
        $this->setData(self::CARD_EXP_MONTH, $expiryMonth);
    }

    /**
     * @inheritDoc
     */
    public function getCcExpYear()
    {
        return $this->_get(self::CARD_EXP_YEAR);
    }

    /**
     * @inheritDoc
     */
    public function setCcExpYear($expiryYear)
    {
        $this->setData(self::CARD_EXP_YEAR, $expiryYear);
    }

    /**
     * @inheritDoc
     */
    public function getCcType()
    {
        return $this->_get(self::CARD_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setCcType($cardType)
    {
        $this->setData(self::CARD_TYPE, $cardType);
    }
}