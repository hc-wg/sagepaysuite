<?php

declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Config;


class ClosedForActionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider actionsDataProvider
     */
    public function testGetActionClosedForPaymentAction(
        string $paymentAction,
        string $expectedTransactionType,
        bool $expectedTransactionStatus
    ) {

        $sut = new \Ebizmarts\SagePaySuite\Model\Config\ClosedForAction($paymentAction);

        list($action, $isClosed) = $sut->getActionClosedForPaymentAction();

        $this->assertEquals($expectedTransactionType, $action);
        $this->assertEquals($expectedTransactionStatus, $isClosed);
    }

    public function actionsDataProvider() : array
    {
        return [
            ['PAYMENT', 'capture', true],
            ['DEFERRED', 'authorization', false],
            ['AUTHENTICATE', 'authorization', false],
            ['Payment', 'capture', true],
            ['REPEATDEFERRED', 'authorization', false],
            ['REPEAT', 'capture', true],
        ];
    }

}