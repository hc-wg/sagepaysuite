<?php
/**
 * Copyright © 2018 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Observer;

use Ebizmarts\SagePaySuite\Helper\Data;
use Ebizmarts\SagePaySuite\Model\Api\Reporting;
use Ebizmarts\SagePaySuite\Observer\SystemConfigEdit;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class SystemConfigEditTest extends \PHPUnit\Framework\TestCase
{
    private $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
    }

    public function testNoChecksRun()
    {
        $observerMock = $this->makeObserverMock();

        $eventMock = $this->makeEventMock();
        $eventMock
            ->expects($this->once())
            ->method('__call')
            ->with(
                $this->equalTo('getRequest')
            )
            ->willReturn($this->makeRequestMock('anothersection'));

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $suiteHelperMock = $this->makeSuiteHelperMock();
        $suiteHelperMock->expects($this->never())->method('verify');

        $messageManagerMock = $this->makeMessageManagerMock();
        $messageManagerMock->expects($this->never())->method('addError');

        $reportingApiMock = $this->makeReportingApiMock();

        $observerModel = $this->objectManagerHelper->getObject(
            SystemConfigEdit::class,
            [
                'suiteHelper'    => $suiteHelperMock,
                'messageManager' => $messageManagerMock,
                'reportingApi'   => $reportingApiMock,
            ]
        );

        $observerModel->execute($observerMock);
    }
    public function testLicenseAndReportingApiChecks()
    {
        $observerMock = $this->makeObserverMock();

        $eventMock = $this->makeEventMock();
        $eventMock
            ->expects($this->once())
            ->method('__call')
            ->with(
                $this->equalTo('getRequest')
            )
            ->willReturn($this->makeRequestMock('payment'));

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $suiteHelperMock = $this->makeSuiteHelperMock();
        $suiteHelperMock->expects($this->once())->method('verify')->willReturn(true);

        $messageManagerMock = $this->makeMessageManagerMock();
        $messageManagerMock->expects($this->never())->method('addError');

        $reportingApiMock = $this->makeReportingApiMock();
        $reportingApiMock->expects($this->once())->method('getVersion')->willReturnSelf();

        $observerModel = $this->objectManagerHelper->getObject(
            SystemConfigEdit::class,
            [
                'suiteHelper'    => $suiteHelperMock,
                'messageManager' => $messageManagerMock,
                'reportingApi'   => $reportingApiMock,
            ]
        );

        $observerModel->execute($observerMock);
    }

    private function makeRequestMock($configSection)
    {
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()->getMock();

        $requestMock->expects($this->once())->method('getParam')->with('section')->willReturn($configSection);

        return $requestMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeObserverMock()
    {
        $observerMock = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();

        return $observerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeEventMock()
    {
        $eventMock = $this->getMockBuilder(Event::class)->disableOriginalConstructor()->getMock();

        return $eventMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeSuiteHelperMock()
    {
        $suiteHelperMock = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();

        return $suiteHelperMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeMessageManagerMock()
    {
        $messageManagerMock = $this->getMockBuilder(ManagerInterface::class)->disableOriginalConstructor()->getMock();

        return $messageManagerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeReportingApiMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        $reportingApiMock = $this->getMockBuilder(Reporting::class)->disableOriginalConstructor()->getMock();

        return $reportingApiMock;
    }

}