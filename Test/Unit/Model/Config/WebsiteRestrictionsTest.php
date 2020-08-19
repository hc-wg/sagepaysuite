<?php
declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Config;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Module\ModuleList;

class WebsiteRestrictionsTest extends \PHPUnit\Framework\TestCase
{
    public function testTheModuleIsKnownAndEnabledInTheRealEnvironment()
    {
        $filePath = realpath(__DIR__) . '/../../../../etc/';
        $dom = new \DOMDocument();
        $dom->load($filePath . 'webrestrictions.xml');

        $converted = $this->convert($dom); //@see \Magento\WebsiteRestriction\Model\Config\Converter::convert

        $this->assertArrayNotHasKey('register', $converted);
        $this->assertArrayHasKey('generic', $converted);
        $this->assertCount(10, $converted['generic']);
        $this->assertContains('sagepaysuite_server_success', $converted['generic']); //do this for all keys that should be present.
    }

    private function convert($source)
    {
        $output = [];
        /** @var \DOMNodeList $actions */
        $actions = $source->getElementsByTagName('action');
        /** @var DOMNode $actionConfig */
        foreach ($actions as $actionConfig) {
            $actionPath = $actionConfig->attributes->getNamedItem('path')->nodeValue;
            $type = $actionConfig->attributes->getNamedItem('type')->nodeValue;
            $output[$type][] = $actionPath;
        }
        return $output;
    }
}
