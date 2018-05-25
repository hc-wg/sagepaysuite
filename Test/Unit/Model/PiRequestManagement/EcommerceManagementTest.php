<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 4/12/18
 * Time: 12:13 PM
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Model\PiRequestManagement\EcommerceManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class EcommerceManagementTest extends \PHPUnit\Framework\TestCase
{

    public function testIsMotoTransaction()
    {
        $objectManagerHelper = new ObjectManager($this);

        /** @var EcommerceManagement $sut */
        $sut = $objectManagerHelper->getObject(EcommerceManagement::class);

        $this->assertFalse($sut->getIsMotoTransaction());
    }

}