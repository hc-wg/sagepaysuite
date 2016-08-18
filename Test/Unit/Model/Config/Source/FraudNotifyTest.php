<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Config\Source;

class FraudNotifyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config\Source\FraudNotify
     */
    protected $fraudNotifyModel;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->fraudNotifyModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Config\Source\FraudNotify',
            []
        );
    }

    public function testToOptionArray()
    {
        $this->assertEquals(
            [
                'value' => "disabled",
                'label' => __('Disabled'),
            ],
            $this->fraudNotifyModel->toOptionArray()[0]
        );
    }
}
