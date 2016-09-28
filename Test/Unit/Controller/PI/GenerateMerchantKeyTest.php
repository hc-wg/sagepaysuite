<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\PI;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class GenerateMerchantKeyTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Ebizmarts\SagePaySuite\Controller\PI\Request
     */
    private $piGenerateMerchantKeyController;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseMock;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultJson;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

        $this->resultJson = $this->getMockBuilder('Magento\Framework\Controller\Result\Json')
            ->disableOriginalConstructor()
            ->getMock();

        $resultFactoryMock = $this->getMockBuilder('Magento\Framework\Controller\ResultFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJson);

        $contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $contextMock->expects($this->any())
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
        $this->piGenerateMerchantKeyController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\GenerateMerchantKey',
            [
                'context' => $contextMock,
                'pirestapi' => $pirestapiMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testExecute()
    {
        $this->_expectResultJson([
            "success" => true,
            'merchant_session_key' => "12345"
        ]);

        $this->piGenerateMerchantKeyController->execute();
    }

    /**
     * @param $result
     */
    private function _expectResultJson($result)
    {
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($result);
    }
}
