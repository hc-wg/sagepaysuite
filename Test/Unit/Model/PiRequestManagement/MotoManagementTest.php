<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 4/12/18
 * Time: 2:01 PM
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Model\PiRequestManagement\MotoManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class MotoManagementTest extends \PHPUnit\Framework\TestCase
{

    public function testIsMotoTransaction()
    {
        $objectManagerHelper = new ObjectManager($this);

        /** @var EcommerceManagement $sut */
        $sut = $objectManagerHelper->getObject(MotoManagement::class);

        $this->assertTrue($sut->getIsMotoTransaction());
    }

}