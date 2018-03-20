<?php
declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Test\Unit\Plugin;


class ExcludeFilesFromMinificationTest extends \PHPUnit\Framework\TestCase
{

    public function testPlugin()
    {

    }

    public function testDiXmlDirectiveExists()
    {
        $configFilePath = BP . DIRECTORY_SEPARATOR . 'app/code/Ebizmarts/SagePaySuite/etc/di.xml';

        $xmlData = \file_get_contents($configFilePath);

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
