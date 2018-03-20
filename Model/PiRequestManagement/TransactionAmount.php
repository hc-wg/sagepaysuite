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
        if (array_key_exists($condition, $this->commands) === false) {
            return $this->commands['DEFAULT'];
        }

        return $this->commands[$condition];
    }

}
