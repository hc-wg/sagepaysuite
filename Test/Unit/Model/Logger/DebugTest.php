<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Logger;

class DebugTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Debug
     */
    private $debugLoggerModel;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->debugLoggerModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Logger\Debug',
            []
        );
    }
    // @codingStandardsIgnoreEnd

    public function testIsHandling()
    {
        $this->assertEquals(
            true,
            $this->debugLoggerModel->isHandling(['level'=>\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_DEBUG])
        );
    }
}
