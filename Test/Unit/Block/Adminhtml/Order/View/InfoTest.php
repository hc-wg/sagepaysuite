<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Adminhtml\Order\View;

class InfoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Order\View\Info
     */
    private $infoBlock;

    /**
     * @var  \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var  \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $registryMock = $this
            ->getMockBuilder('Magento\Framework\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $registryMock->expects($this->once())
            ->method('registry')
            ->willReturn($this->orderMock);

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Backend\Block\Template\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilderMock);

        $this->infoBlock = $this->getObjectManager()
            ->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Order\View\Info',
            [
                'context' => $contextMock,
                'registry' => $registryMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testGetPayment()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $this->assertEquals(
            $paymentMock,
            $this->infoBlock->getPayment()
        );
    }

    public function testGetSyncFromApiUrl()
    {
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('sagepaysuite/order/syncFromApi', ['order_id'=>null]);

        $this->infoBlock->getSyncFromApiUrl();
    }

    public function testGetSuiteHelper()
    {
        $suiteHelperObj = $this->getObjectManager()->getObject('\Ebizmarts\SagePaySuite\Helper\Data');

        $this->infoBlock = $this->getObjectManager()->getObject(
            'Ebizmarts\SagePaySuite\Block\Adminhtml\Order\View\Info',
            [
                'suiteHelper' => $suiteHelperObj
            ]
        );

        $this->assertInstanceOf('\Ebizmarts\SagePaySuite\Helper\Data', $this->infoBlock->getSuiteHelper());
    }

    public function testGetTemplateIsSagePay()
    {
        $configMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->setMethods(['isSagePaySuiteMethod'])
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->once())->method('isSagePaySuiteMethod')->willReturn(true);

        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $registryMock->expects($this->once())->method('registry')->with('current_order')->willReturn($orderMock);

        $infoBlock = $this->getObjectManager()
            ->getObject(
                'Ebizmarts\SagePaySuite\Block\Adminhtml\Order\View\Info',
                [
                    'config'   => $configMock,
                    'registry' => $registryMock
                ]
            );
        $infoBlock->setTemplate('Ebizmarts_SagePaySuite::order/view/info.phtml');

        $this->assertEquals('Ebizmarts_SagePaySuite::order/view/info.phtml', $infoBlock->getTemplate());
    }

    public function testGetTemplateIsNotSagePay()
    {
        $configMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->setMethods(['isSagePaySuiteMethod'])
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->once())->method('isSagePaySuiteMethod')->willReturn(false);

        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $registryMock->expects($this->once())->method('registry')->with('current_order')->willReturn($orderMock);

        /** @var \Ebizmarts\SagePaySuite\Block\Adminhtml\Order\View\Info $infoBlock */
        $infoBlock = $this->getObjectManager()
            ->getObject(
                'Ebizmarts\SagePaySuite\Block\Adminhtml\Order\View\Info',
                [
                    'config'   => $configMock,
                    'registry' => $registryMock
                ]
            );
        $infoBlock->setTemplate('Ebizmarts_SagePaySuite::order/view/info.phtml');

        $this->assertEquals('', $infoBlock->getTemplate());
    }

    /**
     * @return \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private function getObjectManager()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        return $objectManagerHelper;
    }
}
