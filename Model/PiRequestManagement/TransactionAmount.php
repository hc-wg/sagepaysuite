<?php
declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmountJapaneseYen;

class TransactionAmount
{
    /** @var array */
    private $commands = [];

    /**
     * TransactionAmount constructor.
     */
    public function __construct(float $amount)
    {
        $this->commands['JPY'] = new TransactionAmountJapaneseYen($amount);
        $this->commands['KRW'] = new TransactionAmountSouthKoreanWon($amount);
        $this->commands['DEFAULT'] = new TransactionAmountDefault($amount);
    }

    /**
     * @param string $condition
     * @return \Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmountCommandInterface
     */
    public function getCommand($condition) : TransactionAmountCommandInterface
    {
        if (array_key_exists($condition, $this->commands) === false) {
            return $this->commands['DEFAULT'];
        }

        return $this->commands[$condition];
    }

}
