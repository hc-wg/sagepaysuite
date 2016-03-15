<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Config\Source;

class CctypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config\Source\Cctype
     */
    protected $cctypeModel;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cctypeModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Config\Source\Cctype',
            []
        );
    }

    public function testGetAllowedTypes(){
        $this->assertEquals(
            array('VI', 'MC', 'MI', 'AE', 'DN', 'JCB'),
            $this->cctypeModel->getAllowedTypes()
        );
    }
}