<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

class TransactionAmountPost
{
    /** @var array */
    private $commands = [];

    /**
     * TransactionAmount constructor.
     */
    public function __construct($amount)
    {
        $this->commands['JPY'] = new TransactionAmountPostJapaneseYen($amount);
        $this->commands['KRW'] = new TransactionAmountPostSouthKoreanWon($amount);
        $this->commands['DEFAULT'] = new TransactionAmountDefaultPost($amount);
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
