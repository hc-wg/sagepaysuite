<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\PI;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class Callback3DTest extends \PHPUnit\Framework\TestCase
{
    private $objectManagerHelper;

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var /Ebizmarts\SagePaySuite\Controller\PI\Callback3D
     */
    private $piCallback3DController;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseMock;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $redirectMock;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
    }
    // @codingStandardsIgnoreEnd

    public function testExecuteSUCCESS()
    {
        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->makeRequestMock();

        $this->redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->makeContextMock($messageManagerMock);

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $piRequestManagerMock = $this->makeRequestManagerMock();

        $piRequestManagerDataFactoryMock = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $piRequestManagerDataFactoryMock->expects($this->once())->method('create')->willReturn($piRequestManagerMock);

        $resultMock = $this->getMockBuilder('\Ebizmarts\SagePaySuite\Api\Data\PiResult')
            ->disableOriginalConstructor()->getMock();
        $resultMock->expects($this->once())->method('getErrorMessage')->willReturnArgument(null);

        $threeDCallbackManagementMock = $this->makeThreeDCallbackManagementMock($resultMock);

        $this->piCallback3DController = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\Callback3D',
            [
                'context'            => $contextMock,
                'config'             => $configMock,
                'piRequestManagerDataFactory' => $piRequestManagerDataFactoryMock,
                'requester' => $threeDCallbackManagementMock
            ]
        );

        $this->expectSetBody(
            '<script>window.top.location.href = "'
            . $this->urlBuilderMock->getUrl('checkout/onepage/success', ['_secure' => true])
            . '";</script>'
        );

        $this->piCallback3DController->execute();
    }

    public function testExecuteERROR()
    {
        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseMock = $this
                ->getMockBuilder('Magento\Framework\App\Response\Http')
                ->disableOriginalConstructor()
                ->getMock();

        $this->makeRequestMock();

        $this->redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $messageManagerMock->expects($this->once())->method('addError')->with('Invalid 3D secure authentication.');

        $contextMock = $this->makeContextMock($messageManagerMock);

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $piRequestManagerMock = $this->makeRequestManagerMock();

        $piRequestManagerDataFactoryMock = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $piRequestManagerDataFactoryMock->expects($this->once())->method('create')->willReturn($piRequestManagerMock);

        $resultMock = $this->getMockBuilder('\Ebizmarts\SagePaySuite\Api\Data\PiResult')
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock
            ->expects($this->exactly(2))->method('getErrorMessage')->willReturn('Invalid 3D secure authentication.');

        $threeDCallbackManagementMock = $this->makeThreeDCallbackManagementMock($resultMock);

        $this->piCallback3DController = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\Callback3D',
            [
                'context' => $contextMock,
                'config' => $configMock,
                'piRequestManagerDataFactory' => $piRequestManagerDataFactoryMock,
                'requester' => $threeDCallbackManagementMock
            ]
        );
        $this->expectSetBody(
            '<script>window.top.location.href = "'
            . $this->urlBuilderMock->getUrl('checkout/cart', ['_secure' => true])
            . '";</script>'
        );

        $this->piCallback3DController->execute();
    }

    /**
     * @param $body
     */
    private function expectSetBody($body)
    {
        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($body);
    }

    public function testSuccessInvalid3d()
    {
        $this->responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http')
            ->disableOriginalConstructor()
            ->getMock();

        $this->makeRequestMock();

        $this->redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $messageManagerMock = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $messageManagerMock
            ->expects($this->once())
            ->method('addError')
            ->with("Invalid 3D secure authentication.");

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->makeContextMock($messageManagerMock);

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $resultMock = $this->getMockBuilder('\Ebizmarts\SagePaySuite\Api\Data\PiResult')
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock
            ->expects($this->exactly(2))->method('getErrorMessage')->willReturn('Invalid 3D secure authentication.');

        $threeDCallbackManagementMock = $this->makeThreeDCallbackManagementMock($resultMock);

        $piRequestManagerDataFactoryMock = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $piRequestManagerDataFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->makeRequestManagerMock());

        $controller = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\Callback3D',
            [
                'context'            => $contextMock,
                'config'             => $configMock,
                'piRequestManagerDataFactory' => $piRequestManagerDataFactoryMock,
                'requester' => $threeDCallbackManagementMock
            ]
        );

        $this->expectSetBody(
            '<script>window.top.location.href = "'
            . $this->urlBuilderMock->getUrl('checkout/cart', ['_secure' => true])
            . '";</script>'
        );

        $controller->execute();
    }

    /**
     * @param $messageManagerMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeContextMock($messageManagerMock)
    {
        $contextMock = $this
            ->getMockBuilder('Magento\Framework\App\Action\Context')->disableOriginalConstructor()->getMock();
        $contextMock->expects($this->any())->method('getRequest')->will($this->returnValue($this->requestMock));
        $contextMock->expects($this->any())->method('getResponse')->will($this->returnValue($this->responseMock));
        $contextMock->expects($this->any())->method('getRedirect')->will($this->returnValue($this->redirectMock));
        $contextMock->expects($this->any())->method('getMessageManager')->will($this->returnValue($messageManagerMock));
        $contextMock->expects($this->any())->method('getUrl')->will($this->returnValue($this->urlBuilderMock));

        return $contextMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeRequestManagerMock()
    {
        $piRequestManagerMock = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Api\Data\PiRequestManager')
            ->disableOriginalConstructor()->getMock();
        $piRequestManagerMock->expects($this->once())->method('setTransactionId');
        $piRequestManagerMock->expects($this->once())->method('setParEs');
        $piRequestManagerMock->expects($this->once())->method('setVendorName');
        $piRequestManagerMock->expects($this->once())->method('setMode');
        $piRequestManagerMock->expects($this->once())->method('setPaymentAction');

        return $piRequestManagerMock;
    }

    /**
     * @param $resultMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeThreeDCallbackManagementMock($resultMock)
    {
        $threeDCallbackManagementMock = $this
            ->getMockBuilder('\Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement')
            ->disableOriginalConstructor()->getMock();
        $threeDCallbackManagementMock->expects($this->once())->method('setRequestData');
        $threeDCallbackManagementMock->expects($this->once())->method('placeOrder')->willReturn($resultMock);

        return $threeDCallbackManagementMock;
    }

    private function makeRequestMock()
    {
        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()->getMock();
        $this->requestMock->expects($this->any())->method('getParam')->will($this->returnValue(self::TEST_VPSTXID));
        $this->requestMock->expects($this->once())->method('getPost')->will($this->returnValue((object)[
                "PaRes" => "123456780"
            ]));
    }
}
