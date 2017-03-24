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
     * @return string
     */
    public function getCardIdentifier()
    {
        return $this->_get(self::CARD_ID);
    }

    /**
     * @param string $cardId Card identifier.
     * @return void
     */
    public function setCardIdentifier($cardId)
    {
        $this->setData(self::CARD_ID, $cardId);
    }

    /**
     * @return string
     */
    public function getMerchantSessionKey()
    {
        return $this->_get(self::MSK);
    }

    /**
     * @param string $msk
     * @return void
     */
    public function setMerchantSessionKey($msk)
    {
        $this->setData(self::MSK, $msk);
    }

    /**
     * @return int
     */
    public function getCcLastFour()
    {
        return $this->_get(self::CARD_LAST_FOUR);
    }

    /**
     * @param int $lastFour
     * @return void
     */
    public function setCcLastFour($lastFour)
    {
        $this->setData(self::CARD_LAST_FOUR, $lastFour);
    }

    /**
     * @return int
     */
    public function getCcExpMonth()
    {
        return $this->_get(self::CARD_EXP_MONTH);
    }

    /**
     * @param int $expiryMonth
     * @return void
     */
    public function setCcExpMonth($expiryMonth)
    {
        $this->setData(self::CARD_EXP_MONTH, $expiryMonth);
    }

    /**
     * @return int
     */
    public function getCcExpYear()
    {
        return $this->_get(self::CARD_EXP_YEAR);
    }

    /**
     * @param int $expiryYear
     * @return void
     */
    public function setCcExpYear($expiryYear)
    {
        $this->setData(self::CARD_EXP_YEAR, $expiryYear);
    }

    /**
     * @return string
     */
    public function getCcType()
    {
        return $this->_get(self::CARD_TYPE);
    }

    /**
     * @param string $cardType
     * @return void
     */
    public function setCcType($cardType)
    {
        $this->setData(self::CARD_TYPE, $cardType);
    }
}
