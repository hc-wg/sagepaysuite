<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

class TransactionAmountJapaneseYen implements TransactionAmountCommandInterface
{
    /** @var float */
    private $amount;

    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    public function execute()
    {
        return (int)round($this->amount, 0, PHP_ROUND_HALF_EVEN);
    }
}
