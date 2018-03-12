<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Ui\Component\Listing\Column\Fraud;

class FraudTest extends \PHPUnit\Framework\TestCase
{

    const IMAGE_PATH = 'Ebizmarts_SagePaySuite::images/icon-shield-';

    /**
     * @dataProvider thirdmanDataProvider
     */
    public function testGetImageNameThirdman($image, $score)
    {
        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->disableOriginalConstructor()
            ->setMethods(['getImageNameRed'])
            ->getMock();

        $this->assertEquals(self::IMAGE_PATH . $image, $fraudColumnMock->getImageNameThirdman($score));
    }

    /**
     * @dataProvider redDataProvider
     */
    public function testGetImageNameRed($image, $score)
    {
        /** @var  Fraud|PHPUnit_Framework_MockObject_MockObject $fraudColumnMock */
        $fraudColumnMock = $this->getMockBuilder(Fraud::class)
            ->disableOriginalConstructor()
            ->setMethods(['getImageNameThirdman'])
            ->getMock();

        $this->assertEquals(self::IMAGE_PATH . $image, $fraudColumnMock->getImageNameRed($score));
    }

    public function thirdmanDataProvider()
    {
        return [
            "cross 50" =>['cross.png', 50],
            "cross 80" =>['cross.png', 80],
            "zebra 49" =>['zebra.png', 49],
            "zebra 30" =>['zebra.png', 30],
            "zebra 45" =>['zebra.png', 45],
            "check 0" =>['check.png', 0],
            "check 29" =>['check.png', 29],
            "check -10" =>['check.png', -10]
        ];
    }

    public function redDataProvider()
    {
        return [
            "cross" =>['cross.png', 'DENY'],
            "zebra" =>['zebra.png', 'CHALLENGE'],
            "outline" =>['outline.png', 'NOTCHECKED'],
            "check" =>['check.png', 'ACCEPT']
        ];
    }

}