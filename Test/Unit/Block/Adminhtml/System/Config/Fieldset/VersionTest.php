<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\System\Config\Fieldset;

class VersionTest extends \PHPUnit_Framework_TestCase
{
    private $objectManagerHelper;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    }
    // @codingStandardsIgnoreEnd

    public function testGetVersion()
    {
        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock->expects($this->any())
            ->method('getSagePaySuiteModuleVersionNumber')
            ->will($this->returnValue('1.0.0'));

        /** @var \Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version $versionBlock */
        $versionBlock = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version',
            [
                'suiteHelper' => $suiteHelperMock
            ]
        );

        $this->assertEquals(
            '1.0.0',
            $versionBlock->getVersion()
        );
    }

    public function testGetTemplate()
    {
        /** @var \Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version $versionBlock */
        $versionBlock = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version'
        );

        $this->assertEquals(
            'Ebizmarts_SagePaySuite::system/config/fieldset/version.phtml',
            $versionBlock->getTemplate()
        );
    }

    public function testRenderBlank()
    {
        /** @var \Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version $versionBlock */
        $versionBlock = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version'
        );

        $factoryMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\Factory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionFactoryMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $escaperMock = $this->getMockBuilder(\Magento\Framework\Escaper::class)
            ->disableOriginalConstructor()->getMock();

        $args = [
            'factoryElement'    => $factoryMock,
            'factoryCollection' => $collectionFactoryMock,
            'escaper'           => $escaperMock,
            'data'              => ['group' => ['id' => 'not_version']]
        ];
        $renderMock = $this->getMockForAbstractClass(\Magento\Framework\Data\Form\Element\AbstractElement::class, $args)
        ->setMethods(['getData']);

        $this->assertEquals('', $versionBlock->render($renderMock));
    }

    public function testRender()
    {
        /** @var \Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version $versionBlock */
        $versionBlock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Block\Adminhtml\System\Config\Fieldset\Version::class)
            ->setMethods(['toHtml'])
            ->disableOriginalConstructor()
            ->getMock();
        $versionBlock->expects($this->once())->method('toHtml')->willReturn('some html code');

        $factoryMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\Factory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionFactoryMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $escaperMock = $this->getMockBuilder(\Magento\Framework\Escaper::class)
            ->disableOriginalConstructor()->getMock();

        $args = [
            'factoryElement'    => $factoryMock,
            'factoryCollection' => $collectionFactoryMock,
            'escaper'           => $escaperMock,
            'data'              => ['group' => ['id' => 'version']]
        ];
        $renderMock = $this->getMockForAbstractClass(\Magento\Framework\Data\Form\Element\AbstractElement::class, $args)
            ->setMethods(['getData']);

        $this->assertEquals('some html code', $versionBlock->render($renderMock));
    }
}
