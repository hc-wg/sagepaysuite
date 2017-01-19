<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\PI;

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

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
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
            'Ebizmarts\SagePaySuite\Controller\PI\GenerateMerchantKey',
            [
                'context' => $contextMock,
                'pirestapi' => $pirestapiMock
            ]
        );

        $resultJson
            ->expects($this->once())
            ->method('setData')
            ->with([
                "success" => true,
                'merchant_session_key' => "12345"
            ]);

        $piGenerateMerchantKeyController->execute();
    }

    public function testExecuteApiException()
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

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($responseMock));
        $contextMock->expects($this->once())
            ->method('getResultFactory')
            ->will($this->returnValue($resultFactoryMock));

        $error        = new \Magento\Framework\Phrase("Authentication values are missing");
        $apiException = new \Ebizmarts\SagePaySuite\Model\Api\ApiException($error, null, '1001');

        $pirestapiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')
            ->disableOriginalConstructor()
            ->getMock();
        $pirestapiMock
            ->expects($this->once())
            ->method('generateMerchantKey')
            ->willThrowException($apiException);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $piGenerateMerchantKeyController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\GenerateMerchantKey',
            [
                'context'   => $contextMock,
                'pirestapi' => $pirestapiMock
            ]
        );

        $resultJson
            ->expects($this->once())
            ->method('setData')
            ->with([
                "success"       => false,
                'error_message' => $error
            ]);

        $piGenerateMerchantKeyController->execute();
    }

    public function testExecuteException()
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

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($responseMock));
        $contextMock->expects($this->once())
            ->method('getResultFactory')
            ->will($this->returnValue($resultFactoryMock));

        $error = new \Magento\Framework\Phrase("Something went wrong while generating the merchant session key.");

        $pirestapiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')
            ->disableOriginalConstructor()
            ->getMock();
        $pirestapiMock
            ->expects($this->once())
            ->method('generateMerchantKey')
            ->willThrowException(new \Exception($error));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $piGenerateMerchantKeyController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\GenerateMerchantKey',
            [
                'context'   => $contextMock,
                'pirestapi' => $pirestapiMock
            ]
        );

        $resultJson
            ->expects($this->once())
            ->method('setData')
            ->with([
                "success"       => false,
                'error_message' => $error
            ]);

        $piGenerateMerchantKeyController->execute();
    }
}
