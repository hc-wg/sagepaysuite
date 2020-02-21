<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 2020-02-21
 * Time: 11:31
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Cart;

use Ebizmarts\SagePaySuite\Controller\Cart\Recover;
use Ebizmarts\SagePaySuite\Model\RecoverCartAndCancelOrder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class RecoverTest extends \PHPUnit\Framework\TestCase
{
    public function testExecute()
    {
        $recoverCartAndCancelOrderMock = $this
            ->getMockBuilder(RecoverCartAndCancelOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $recoverCartAndCancelOrderMock
            ->expects($this->once())
            ->method('execute')
            ->with(false);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $recover = $objectManagerHelper->getObject(
            '\Ebizmarts\SagePaySuite\Controller\Cart\Recover',
            [
                'recoverCartAndCancelOrder' => $recoverCartAndCancelOrderMock
            ]
        );

        $recover->execute();
    }

}
