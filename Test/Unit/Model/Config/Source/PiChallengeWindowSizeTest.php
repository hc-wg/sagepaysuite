<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Config\Source;

use Ebizmarts\SagePaySuite\Model\Config\Source\PiChallengeWindowSize;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class PiChallengeWindowSizeTest extends \PHPUnit\Framework\TestCase
{
    const CHALLENGE_WINDOW_SIZE_OPTIONS_COUNT = 5;

    public function testToOptionArray()
    {
        $objectManagerHelper = new ObjectManager($this);
        $challengeWindowSize = $objectManagerHelper->getObject(PiChallengeWindowSize::class);

        $availableOptions = $challengeWindowSize->toOptionArray();

        $this->assertEquals(
            [
                'value' => '01',
                'label' => '250px x 400px',
            ],
            $availableOptions[0]
        );
        $this->assertEquals(
            [
                'value' => '02',
                'label' => '390px x 400px',
            ],
            $availableOptions[1]
        );
        $this->assertEquals(
            [
                'value' => '03',
                'label' => '500px x 600px',
            ],
            $availableOptions[2]
        );
        $this->assertEquals(
            [
                'value' => '04',
                'label' => '600px x 400px',
            ],
            $availableOptions[3]
        );
        $this->assertEquals(
            [
                'value' => '05',
                'label' => 'Fullscreen',
            ],
            $availableOptions[4]
        );

        $this->assertCount(self::CHALLENGE_WINDOW_SIZE_OPTIONS_COUNT, $availableOptions);
    }
}
