<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

class ThreeDSecureCallbackManagementTest extends \PHPUnit_Framework_TestCase
{

    public function testIsNotMotoTransaction()
    {
        /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $model */
        $model = $this
            ->getMockBuilder("Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement")
            ->disableOriginalConstructor()
            ->setMethods(["getPayment"])
            ->getMock();

        $this->assertFalse($model->getIsMotoTransaction());
    }

}