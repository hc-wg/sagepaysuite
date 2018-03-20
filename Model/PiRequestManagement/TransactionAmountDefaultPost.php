<?php

namespace Ebizmarts\SagePaySuite\Model\PiRequestManagement;

class TransactionAmountDefaultPost implements TransactionAmountPostCommandInterface
{
    /** @var float */
    private $amount;

    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    public function execute()
    {
        return number_format($this->amount, 2, '.', '');
    }
}
