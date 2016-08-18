<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Logger;

class CronTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Cron
     */
    protected $cronLoggerModel;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cronLoggerModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Logger\Cron',
            []
        );
    }

    public function testIsHandling()
    {
        $this->assertEquals(
            true,
            $this->cronLoggerModel->isHandling(['level'=>\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON])
        );
    }
}
