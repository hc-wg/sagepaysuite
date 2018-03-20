<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

class TransactionAmountDefaultPi implements TransactionAmountCommandInterface
{
    /** @var float */
    private $amount;

    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    public function execute()
    {
        return (int)($this->amount * 100);
    }
}
