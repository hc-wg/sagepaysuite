<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Form;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Controller\Form\Request
     */
    protected $formRequestController;

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

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_configMock;

    protected function setUp()
    {
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

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
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
            ->method('getUrl')
            ->will($this->returnValue($this->urlBuilderMock));
        $contextMock->expects($this->any())
            ->method('getResultFactory')
            ->will($this->returnValue($resultFactoryMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));

        $this->_configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

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
        $requestHelperMock->expects($this->any())
            ->method('populatePaymentAmount')
            ->will($this->returnValue(array()));
        $requestHelperMock->expects($this->any())
            ->method('getOrderDescription')
            ->will($this->returnValue("description"));
        $requestHelperMock->expects($this->any())
            ->method('populateBasketInformation')
            ->will($this->returnValue([]));

        $cryptMock = $this
            ->getMockBuilder('Crypt_AES')
            ->disableOriginalConstructor()
            ->getMock();
        $cryptMock->expects($this->any())
            ->method('encrypt')
            ->will($this->returnValue("786234786234786234786234867234768324"));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->formRequestController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Request',
            [
                'context' => $contextMock,
                'config' => $this->_configMock,
                'checkoutSession' => $checkoutSessionMock,
                'requestHelper' => $requestHelperMock,
                'suiteHelper' => $suiteHelperMock,
                'crypt' => $cryptMock
            ]
        );
    }

    public function testExecuteOK()
    {
        $this->_configMock->expects($this->once())
            ->method('getFormEncryptedPassword')
            ->will($this->returnValue("1234567890"));

        $this->_expectResultJson([
            "success" => true,
            'redirect_url' => \Ebizmarts\SagePaySuite\Model\Config::URL_FORM_REDIRECT_TEST,
            "vps_protocol" => NULL,
            "tx_type" => NULL,
            "vendor" => NULL,
            "crypt" => "@373836323334373836323334373836323334373836323334383637323334373638333234"
        ]);

        $this->formRequestController->execute();
    }

    public function testExecuteERROR()
    {
        $this->_configMock->expects($this->any())
            ->method('getFormEncryptedPassword')
            ->will($this->returnValue(NULL));

        $this->_expectResultJson([
            "success" => false,
            'error_message' => "Something went wrong: Invalid FORM encrypted password."
        ]);

        $this->formRequestController->execute();
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
