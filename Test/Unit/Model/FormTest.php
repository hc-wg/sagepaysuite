<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class FormTest extends \PHPUnit_Framework_TestCase
{
    private $objectManagerHelper;

    /** @var \Ebizmarts\SagePaySuite\Model\Form */
    private $formModelObject;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->formModelObject = $this->objectManagerHelper->getObject('\Ebizmarts\SagePaySuite\Model\Form');
    }
    // @codingStandardsIgnoreEnd

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Invalid response from Sage Pay
     */
    public function testDecodeSagePayResponseEmpty()
    {
        $this->formModelObject->decodeSagePayResponse("");
    }

    public function testGetCode()
    {
        $this->assertEquals('sagepaysuiteform', $this->formModelObject->getCode());
    }

    public function testGetInfoBlockType()
    {
        $this->assertEquals('Ebizmarts\SagePaySuite\Block\Info', $this->formModelObject->getInfoBlockType());
    }

    public function testIsGateway()
    {
        $this->assertTrue($this->formModelObject->isGateway());
    }

    public function testCanOrder()
    {
        $this->assertTrue($this->formModelObject->canOrder());
    }

    public function testCanAuthorize()
    {
        $this->assertTrue($this->formModelObject->canAuthorize());
    }

    public function testCanCapture()
    {
        $this->assertTrue($this->formModelObject->canCapture());
    }

    public function testCanCapturePartial()
    {
        $this->assertTrue($this->formModelObject->canCapturePartial());
    }

    public function testCanRefund()
    {
        $this->assertTrue($this->formModelObject->canRefund());
    }

    public function testCanRefundPartialPerInvoice()
    {
        $this->assertTrue($this->formModelObject->canRefundPartialPerInvoice());
    }

    public function testCanUseCheckout()
    {
        $this->assertTrue($this->formModelObject->canUseCheckout());
    }

    public function testCanFetchTransactionInfo()
    {
        $this->assertTrue($this->formModelObject->canFetchTransactionInfo());
    }

    public function testCanReviewPayment()
    {
        $this->assertTrue($this->formModelObject->canReviewPayment());
    }

    public function testmarkAsInitialized()
    {
        $this->assertTrue($this->formModelObject->isInitializeNeeded());
        $this->formModelObject->markAsInitialized();
        $this->assertFalse($this->formModelObject->isInitializeNeeded());
    }

    public function testIsInitializedNeeded()
    {
        $this->assertTrue($this->formModelObject->isInitializeNeeded());
    }

    public function testCanUseInternal()
    {
        $configMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->any())->method('setMethodCode')->with('sagepaysuiteform')->willReturnSelf();
        $configMock->expects($this->once())->method('isMethodActiveMoto')->willReturn(1);

        $form = $this->objectManagerHelper->getObject(
            '\Ebizmarts\SagePaySuite\Model\Form',
            [
                'config' => $configMock,
            ]
        );

        $this->assertTrue($form->canUseInternal());
    }

    public function testIsActive()
    {
        $scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeConfigMock->expects($this->any())->method('getValue')
            ->with('payment/sagepaysuiteform/active')
            ->willReturn(1);

        $appStateMock = $this->getMockBuilder(\Magento\Framework\App\State::class)
            ->disableOriginalConstructor()->getMock();
        $appStateMock->expects($this->once())->method('getAreaCode')->willReturn('frontend');

        $contextMock = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())->method('getAppState')->willReturn($appStateMock);

        $form = $this->objectManagerHelper->getObject(
            '\Ebizmarts\SagePaySuite\Model\Form',
            [
                'context'     => $contextMock,
                'scopeConfig' => $scopeConfigMock
            ]
        );

        $this->assertTrue($form->isActive());
    }

    public function testIsActiveMoto()
    {
        $scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeConfigMock->expects($this->any())->method('getValue')
            ->with('payment/sagepaysuiteform/active_moto')
            ->willReturn(1);

        $appStateMock = $this->getMockBuilder(\Magento\Framework\App\State::class)
            ->disableOriginalConstructor()->getMock();
        $appStateMock->expects($this->once())->method('getAreaCode')->willReturn('adminhtml');

        $contextMock = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())->method('getAppState')->willReturn($appStateMock);

        $form = $this->objectManagerHelper->getObject(
            '\Ebizmarts\SagePaySuite\Model\Form',
            [
                'context'     => $contextMock,
                'scopeConfig' => $scopeConfigMock
            ]
        );

        $this->assertTrue($form->isActive());
    }

    public function testInitialize()
    {
        $stateObjectMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['setState', 'setStatus', 'setIsNotified'])
            ->disableOriginalConstructor()->getMock();
        $stateObjectMock->expects($this->once())->method('setState')->with('pending_payment');
        $stateObjectMock->expects($this->once())->method('setStatus')->with('pending_payment');
        $stateObjectMock->expects($this->once())->method('setIsNotified')->with(false);

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(['setCanSendNewEmailFlag'])
            ->disableOriginalConstructor()->getMock();
        $orderMock->expects($this->once())->method('setCanSendNewEmailFlag')->with(false);

        $infoInstanceMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->setMethods(
                [
                    'getOrder',
                    'encrypt',
                    'decrypt',
                    'setAdditionalInformation',
                    'getMethodInstance',
                    'hasAdditionalInformation',
                    'getAdditionalInformation',
                    'unsAdditionalInformation'
                ]
            )
            ->disableOriginalConstructor()->getMock();
        $infoInstanceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->formModelObject->setInfoInstance($infoInstanceMock);

        $this->formModelObject->initialize('authorize_capture', $stateObjectMock);
    }

    public function testCanVoid()
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(['getState'])
            ->disableOriginalConstructor()->getMock();
        $orderMock->expects($this->once())->method('getState')->willReturn('pending_payment');

        $infoInstanceMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->setMethods(
                [
                    'getOrder',
                    'encrypt',
                    'decrypt',
                    'setAdditionalInformation',
                    'getMethodInstance',
                    'hasAdditionalInformation',
                    'getAdditionalInformation',
                    'unsAdditionalInformation'
                ]
            )
            ->disableOriginalConstructor()->getMock();
        $infoInstanceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->formModelObject->setInfoInstance($infoInstanceMock);

        $this->assertFalse($this->formModelObject->canVoid());
    }

    public function testCanVoidYes()
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(['getState'])
            ->disableOriginalConstructor()->getMock();
        $orderMock->expects($this->once())->method('getState')->willReturn('processing');

        $infoInstanceMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->setMethods(
                [
                    'getOrder',
                    'encrypt',
                    'decrypt',
                    'setAdditionalInformation',
                    'getMethodInstance',
                    'hasAdditionalInformation',
                    'getAdditionalInformation',
                    'unsAdditionalInformation'
                ]
            )
            ->disableOriginalConstructor()->getMock();
        $infoInstanceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->formModelObject->setInfoInstance($infoInstanceMock);

        $this->assertTrue($this->formModelObject->canVoid());
    }
}
