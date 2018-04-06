<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

class TransactionAmountPostJapaneseYen implements TransactionAmountPostCommandInterface
{
    /** @var float */
    private $amount;

    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    public function execute()
    {
        return (string)round($this->amount, 0, PHP_ROUND_HALF_EVEN);
    }
}
