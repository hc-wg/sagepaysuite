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
    private $versionBlock;

    // @codingStandardsIgnoreStart
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
    // @codingStandardsIgnoreEnd

    public function testGetVersion()
    {
        $this->assertEquals(
            '1.0.0',
            $this->versionBlock->getVersion()
        );
    }
}
