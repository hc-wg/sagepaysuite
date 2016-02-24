<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Server;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * TEST_POST_REQUEST
     */
    protected $TEST_POST_REQUEST;

    /**
     * @var Delete
     */
    protected $serverRequestController;

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

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJson;

    protected function setUp()
    {
        $this->TEST_POST_REQUEST = 'Content-Language: en-GB' . PHP_EOL . PHP_EOL . '{"save_token": "false", "token":"null"}';

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getGrandTotal')
            ->will($this->returnValue(100));
        $quoteMock->expects($this->any())
            ->method('getQuoteCurrencyCode')
            ->will($this->returnValue('USD'));
        $quoteMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

        $checkoutSessionMock = $this
            ->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quoteMock));

        $this->responseMock = $this
            ->getMock('Magento\Framework\App\Response\Http', [], [], '', false);

        $this->urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

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
            ->method('getRequest')
            ->will($this->returnValue($this->TEST_POST_REQUEST));
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $contextMock->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));
        $contextMock->expects($this->any())
            ->method('getResultFactory')
            ->will($this->returnValue($resultFactoryMock));

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->any())
            ->method('getVPSProtocol')
            ->will($this->returnValue("3.00"));
        $configMock->expects($this->any())
            ->method('getSagepayPaymentAction')
            ->will($this->returnValue("PAYMENT"));
        $configMock->expects($this->any())
            ->method('getVendorname')
            ->will($this->returnValue("testebizmarts"));
        $configMock->expects($this->any())
            ->method('getVPSProtocol')
            ->will($this->returnValue("3.00"));
        $configMock->expects($this->any())
            ->method('getMode')
            ->will($this->returnValue("live"));

        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock->expects($this->any())
            ->method('generateVendorTxCode')
            ->will($this->returnValue("10000001-2015-12-12-12-12345"));

        $requestHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $requestHelperMock->expects($this->any())
            ->method('populateAddressInformation')
            ->will($this->returnValue(array()));

        $postApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Post')
            ->disableOriginalConstructor()
            ->getMock();
        $postApiMock->expects($this->any())
            ->method('sendPost')
            ->will($this->returnValue([
                "status" => 200,
                "data" => [
                    "VPSTxId" => "{" . self::TEST_VPSTXID . "}"
                ]
            ]));

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

        $checkoutHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Checkout')
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue($orderMock));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->serverRequestController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Server\Request',
            [
                'context' => $contextMock,
                'config' => $configMock,
                'checkoutSession' => $checkoutSessionMock,
                'requestHelper' => $requestHelperMock,
                'checkoutHelper' => $checkoutHelperMock,
                'suiteHelper' => $suiteHelperMock,
                'postApi' => $postApiMock
            ]
        );
    }

    public function testExecute()
    {
        $this->_expectResultJson([
            "success" => true,
            'response' => [
                "status" => 200,
                "data" => [
                    "VPSTxId" => "{" . self::TEST_VPSTXID . "}"
                ]
            ]
        ]);

        $this->serverRequestController->execute();
    }

    /**
     * @param $result
     */
    protected function _expectResultJson($result)
    {
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($result);
    }

}
