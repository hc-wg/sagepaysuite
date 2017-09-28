<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Adminhtml\PI;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class GenerateMerchantKeyTest extends \PHPUnit\Framework\TestCase
{
    public function testExecute()
    {
        $responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $resultJson = $this
            ->getMockBuilder('Magento\Framework\Controller\Result\Json')
            ->disableOriginalConstructor()
            ->getMock();

        $resultFactoryMock = $this
            ->getMockBuilder('Magento\Framework\Controller\ResultFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);

        $contextMock = $this->getMockBuilder('Magento\Backend\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->once())
            ->method('getResponse')
            ->willReturn($responseMock);
        $contextMock->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($resultFactoryMock);

        $mskResultMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mskResultMock
            ->expects($this->once())
            ->method('getSuccess')
            ->willReturn(true);
        $mskResultMock
            ->expects($this->once())
            ->method('__toArray')
            ->willReturn(['success' => true, 'response' => '12345']);
        $piServiceMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PiMsk::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piServiceMock
            ->expects($this->once())
            ->method('getSessionKey')
            ->willReturn($mskResultMock);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $piGenerateMerchantKeyController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Adminhtml\PI\GenerateMerchantKey',
            [
                'context' => $contextMock,
                'piMsk'   => $piServiceMock
            ]
        );

        $resultJson
            ->expects($this->once())
            ->method('setData')
            ->with([
                "success"  => true,
                'response' => "12345"
            ]);

        $piGenerateMerchantKeyController->execute();
    }

    public function testExecuteApiException()
    {
        $responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $resultJson = $this->getMockBuilder('Magento\Framework\Controller\Result\Json')
            ->disableOriginalConstructor()
            ->getMock();

        $resultFactoryMock = $this->getMockBuilder('Magento\Framework\Controller\ResultFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);

        $messageManagerMock = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $messageManagerMock
            ->expects($this->once())
            ->method('addError')
            ->with("Something went wrong: Authentication values are missing");

        $contextMock = $this->getMockBuilder('Magento\Backend\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($responseMock));
        $contextMock->expects($this->once())
            ->method('getResultFactory')
            ->will($this->returnValue($resultFactoryMock));
        $contextMock->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($messageManagerMock);

        $mskResultMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Api\Data\Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mskResultMock
            ->expects($this->once())
            ->method('getSuccess')
            ->willReturn(false);
        $mskResultMock
            ->expects($this->once())
            ->method('getErrorMessage')
            ->willReturn('Authentication values are missing');
        $mskResultMock
            ->expects($this->once())
            ->method('__toArray')
            ->willReturn(
                [
                    'success'       => false,
                    'error_message' => 'Authentication values are missing'
                ]
            );
        $piServiceMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PiMsk::class)
            ->disableOriginalConstructor()
            ->getMock();
        $piServiceMock
            ->expects($this->once())
            ->method('getSessionKey')
            ->willReturn($mskResultMock);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $piGenerateMerchantKeyController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Adminhtml\PI\GenerateMerchantKey',
            [
                'context' => $contextMock,
                'piMsk'   => $piServiceMock
            ]
        );

        $resultJson
            ->expects($this->once())
            ->method('setData')
            ->with([
                "success"       => false,
                "error_message" => "Authentication values are missing"
            ]);

        $piGenerateMerchantKeyController->execute();
    }
}
