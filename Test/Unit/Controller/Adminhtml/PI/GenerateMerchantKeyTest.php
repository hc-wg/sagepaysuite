<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Adminhtml\PI;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class GenerateMerchantKeyTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

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

        $contextMock = $this->getMockBuilder('Magento\Backend\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($responseMock));
        $contextMock->expects($this->once())
            ->method('getResultFactory')
            ->will($this->returnValue($resultFactoryMock));

        $pirestapiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')
            ->disableOriginalConstructor()
            ->getMock();
        $pirestapiMock->expects($this->once())
            ->method('generateMerchantKey')
            ->will($this->returnValue("12345"));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $piGenerateMerchantKeyController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Adminhtml\PI\GenerateMerchantKey',
            [
                'context' => $contextMock,
                'pirestapi' => $pirestapiMock
            ]
        );

        $resultJson->expects($this->once())
            ->method('setData')
            ->with([
                "success"              => true,
                'merchant_session_key' => "12345"
            ]);

        $piGenerateMerchantKeyController->execute();
    }

    public function testExecuteApiException()
    {
        $error = new \Magento\Framework\Phrase("Authentication values are missing");

        $responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

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
        $messageManagerMock->expects($this->once())->method('addError')->with($error);

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

        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException($error, null, '1001');
        $pirestapiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')
            ->disableOriginalConstructor()
            ->getMock();
        $pirestapiMock->expects($this->once())
            ->method('generateMerchantKey')
            ->willThrowException($apiException);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $piGenerateMerchantKeyController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Adminhtml\PI\GenerateMerchantKey',
            [
                'context' => $contextMock,
                'pirestapi' => $pirestapiMock
            ]
        );

        $resultJson
            ->expects($this->once())
            ->method('setData')
            ->with([
                "success"       => false,
                "error_message" => $error
            ]);

        $piGenerateMerchantKeyController->execute();
    }

    public function testExecuteException()
    {
        $error        = new \Magento\Framework\Phrase("Sage Pay is not available.");
        $errorMessage = new \Magento\Framework\Phrase("Something went wrong: Sage Pay is not available.");

        $responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

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
        $messageManagerMock->expects($this->once())->method('addError')->with($errorMessage);

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

        $pirestapiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')
            ->disableOriginalConstructor()
            ->getMock();
        $pirestapiMock->expects($this->once())
            ->method('generateMerchantKey')
            ->willThrowException(new \Exception($error));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $piGenerateMerchantKeyController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Adminhtml\PI\GenerateMerchantKey',
            [
                'context' => $contextMock,
                'pirestapi' => $pirestapiMock
            ]
        );

        $resultJson
            ->expects($this->once())
            ->method('setData')
            ->with([
                "success"       => false,
                "error_message" => $errorMessage
            ]);

        $piGenerateMerchantKeyController->execute();
    }
}
