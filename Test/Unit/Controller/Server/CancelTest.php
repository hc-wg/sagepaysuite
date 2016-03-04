<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class CancelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Controller\Server\Cancel
     */
    protected $serverCancelController;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    protected function setUp()
    {
        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\RequestInterface')
            ->getMockForAbstractClass();
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn("Error message");

        $this->responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $messageManagerMock->expects($this->once())
            ->method('addError')
            ->will($this->returnValue($this->requestMock));

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));
        $contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->serverCancelController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Cancel',
            [
                'context' => $contextMock
            ]
        );
    }

    public function testExecute()
    {
        $this->_expectSetBody(
            '<script>window.top.location.href = "'
            . '";</script>'
        );

        $this->serverCancelController->execute();
    }

    /**
     * @param $body
     */
    protected function _expectSetBody($body)
    {
        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($body);
    }
}
