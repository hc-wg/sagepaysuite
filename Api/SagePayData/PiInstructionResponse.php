<?php

namespace Ebizmarts\SagePaySuite\Api\SagePayData;

class PiInstructionResponse extends \Magento\Framework\Api\AbstractExtensibleObject implements PiInstructionResponseInterface
{
    /**
     * @inheritDoc
     */
    public function getInstructionType()
    {
        return $this->_get(self::INSTRUCTION_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setInstructionType($instructionType)
    {
        $this->setData(self::INSTRUCTION_TYPE, $instructionType);
    }

    /**
     * @inheritDoc
     */
    public function getDate()
    {
        return $this->_get(self::DATE);
    }

    /**
     * @inheritDoc
     */
    public function setDate($date)
    {
        $this->setData(self::DATE, $date);
    }
}
