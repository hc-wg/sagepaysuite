<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

class TransactionAmount
{
    /** @var array */
    private $commands = [];

    /**
     * TransactionAmount constructor.
     */
    public function __construct($amount)
    {
        $this->commands['JPY'] = new TransactionAmountJapaneseYen($amount);
        $this->commands['KRW'] = new TransactionAmountSouthKoreanWon($amount);
        $this->commands['DEFAULT'] = new TransactionAmountDefaultPi($amount);
    }

    /**
     * @param string $condition
     * @return \Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmountCommandInterface
     */
    public function getCommand($condition)
    {
        if (isset($this->commands[$condition]) === false) {
            return $this->commands['DEFAULT'];
        }

        return $this->commands[$condition];
    }
}
