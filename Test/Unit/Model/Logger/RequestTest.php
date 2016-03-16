<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Logger;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Request
     */
    protected $requestLoggerModel;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->requestLoggerModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Logger\Request',
            []
        );
    }

    public function testIsHandling()
    {
        $this->assertEquals(
            true,
            $this->requestLoggerModel->isHandling(['level'=>\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_REQUEST])
        );
    }
}