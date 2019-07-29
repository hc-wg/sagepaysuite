<?php
declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Test\Unit\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Asset\Minification;

class ExcludeFilesFromMinificationTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider pluginDataprovider
     */
    public function testPluginJsEmptyResult(array $result, string $contentType, array $expected)
    {
        $minificationMock = $this->createMock(Minification::class);
        $objectManager = new ObjectManager($this);

        /** @var \Ebizmarts\SagePaySuite\Plugin\ExcludeFilesFromMinification $excludesPlugin */
        $excludesPlugin = $objectManager->getObject(\Ebizmarts\SagePaySuite\Plugin\ExcludeFilesFromMinification::class);

        $pluginResult = $excludesPlugin->afterGetExcludes($minificationMock, $result, $contentType);

        $this->assertEquals($expected, $pluginResult);
    }

    public function pluginDataprovider() : array
    {
        return [
            [[], 'js', ['api/v1/js/sagepay']],
            [[], 'css', []],
            [['/tiny_mce/'], 'js', ['/tiny_mce/', 'api/v1/js/sagepay']],
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
