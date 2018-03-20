<?php
namespace Ebizmarts\SagePaySuite\Test\Unit\Plugin;

use Ebizmarts\SagePaySuite\Plugin\ExcludeFilesFromMinification;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Asset\Minification;

class ExcludeFilesFromMinificationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider pluginDataprovider
     */
    public function testPluginJsEmptyResult($contentType, $expected, $callable)
    {
        $minificationMock = $this->getMockBuilder(Minification::class)
        ->disableOriginalConstructor()->getMock();
        $objectManager = new ObjectManager($this);

        /** @var ExcludeFilesFromMinification $excludesPlugin */
        $excludesPlugin = $objectManager->getObject(ExcludeFilesFromMinification::class);

        $pluginResult = $excludesPlugin->aroundGetExcludes($minificationMock, $callable, $contentType);

        $this->assertEquals($expected, $pluginResult);
    }

    public function pluginDataprovider()
    {
        return [
            ['js', ['api/v1/js/sagepay'], function($contentType) {
                $this->assertEquals('js', $contentType);
                return [];
            }],
            ['css', ['test'], function($contentType) {
                $this->assertEquals('css', $contentType);
                return ['test'];
            }],
            ['js', ['/tiny_mce/', 'api/v1/js/sagepay'], function($contentType) {
                $this->assertEquals('js', $contentType);
                return ['/tiny_mce/'];
            }],
        ];
    }

    public function testDiXmlDirectiveExists()
    {
        $configFilePath = BP . DIRECTORY_SEPARATOR . 'app/code/Ebizmarts/SagePaySuite/etc/di.xml';

        $xmlData = \file_get_contents($configFilePath); //@codingStandardsIgnoreLine

        $xml = new \SimpleXMLElement($xmlData);

        $pluginConfig = $xml->xpath('/config/type[@name="Magento\Framework\View\Asset\Minification"]');

        $this->assertNotEmpty($pluginConfig);
        $this->assertCount(1, $pluginConfig);

        $pluginNode = $pluginConfig[0];

        $pluginNodeAttributes = $pluginNode->plugin->attributes();
        $this->assertCount(2, $pluginNodeAttributes);

        $this->assertEquals('preventPiRemoteJsMinification', $pluginNodeAttributes['name']);
        $this->assertEquals('Ebizmarts\SagePaySuite\Plugin\ExcludeFilesFromMinification', $pluginNodeAttributes['type']);
    }

}
