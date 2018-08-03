<?php
/**
 * Copyright © 2018 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Model\PiRequestManagement\EcommerceManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class EcommerceManagementTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    private $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManager($this);
    }

    public function testIsMotoTransaction()
    {
        $objectManagerHelper = new ObjectManager($this);

        /** @var EcommerceManagement $sut */
        $sut = $this->objectManagerHelper->getObject(EcommerceManagement::class);

        $this->assertFalse($sut->getIsMotoTransaction());
    }

}