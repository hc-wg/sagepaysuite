<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\System\Config\Fieldset;

class VersionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version
     */
    protected $versionBlock;

    protected function setUp()
    {
        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.0'));

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->versionBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version',
            [
                'suiteHelper' => $suiteHelperMock
            ]
        );
    }

    public function testGetVersion()
    {
        $this->assertEquals(
            '1.0.0',
            $this->versionBlock->getVersion()
        );
    }

    public function testGetPxParams()
    {
        $this->assertEquals(
            'ext=Sage Pay Suite M2;1.0.0&mage=Magento ;&ctrl=9bd1cea438e2b7a1ff1bb82a8664b553',
            $this->versionBlock->getPxParams()
        );
    }
}