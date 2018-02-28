<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;


use Ebizmarts\SagePaySuite\Model\PiRequestManagement\TransactionAmountJapaneseYen;

class TransactionAmountJapaneseYenTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider amountsProvider
     */
    public function testAmounts($expected, $amount)
    {
        $amountObject = new TransactionAmountJapaneseYen($amount);

        $this->assertEquals($expected, $amountObject->execute());
    }

    public function amountsProvider()
    {
        return [
            [1, 1],
            [9540, 9539.84],
            [6469, 6469.20]
        ];
    }

}
