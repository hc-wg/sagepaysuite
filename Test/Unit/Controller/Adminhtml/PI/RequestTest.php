<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Adminhtml\PI;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Controller\Adminhtml\PI\Request
     */
    protected $piRequestController;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var QuoteSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteSessionMock;

    /**
     * @var  \Magento\Quote\Model\QuoteManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteManagementMock;

    /**
     * @var  Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJson;

    protected function setUp()
    {
        $piModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\PI')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($piModelMock));

        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
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
        $quoteMock->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($addressMock));

        $quoteSessionMock = $this
            ->getMockBuilder('Magento\Backend\Model\Session\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quoteMock));

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

        $requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())
            ->method('getPost')
            ->will($this->returnValue((object)[
                "merchant_session_Key" => "12345",
                "card_identifier" => "12345",
                "card_last4" => "0006",
                "card_exp_month" => "02",
                "card_exp_year" => "22",
                "card_type" => "VISA"
            ]));

        $urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Backend\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($requestMock));
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $contextMock->expects($this->any())
            ->method('getResultFactory')
            ->will($this->returnValue($resultFactoryMock));
        $contextMock->expects($this->any())
            ->method('getBackendUrl')
            ->will($this->returnValue($urlBuilderMock));

        $configMock = $this
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

        $pirestapiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')
            ->disableOriginalConstructor()
            ->getMock();
        $pirestapiMock->expects($this->any())
            ->method('capture')
            ->will($this->returnValue((object)[
                "statusCode" => \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS,
                "transactionId" => self::TEST_VPSTXID,
                "statusDetail" => 'OK Status'
            ]));

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();

        $this->quoteManagementMock = $this
            ->getMockBuilder('Magento\Quote\Model\QuoteManagement')
            ->disableOriginalConstructor()
            ->getMock();

        $requestHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $requestHelperMock->expects($this->any())
            ->method('populatePaymentAmount')
            ->will($this->returnValue(array()));
        $requestHelperMock->expects($this->any())
            ->method('getOrderDescription')
            ->will($this->returnValue("description"));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->piRequestController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Adminhtml\PI\Request',
            [
                'context' => $contextMock,
                'config' => $configMock,
                'suiteHelper' => $suiteHelperMock,
                'pirestapi' => $pirestapiMock,
                'quoteSession' => $quoteSessionMock,
                'quoteManagement' => $this->quoteManagementMock,
                'requestHelper' => $requestHelperMock
            ]
        );
    }

    public function testExecuteSUCCESS()
    {
        $this->quoteManagementMock->expects($this->any())
            ->method('submit')
            ->will($this->returnValue($this->orderMock));

        $this->_expectResultJson([
            "success" => true,
            'response' => (object)[
                "statusCode" => 0000,
                "transactionId" => self::TEST_VPSTXID,
                "statusDetail" => "OK Status",
                "redirect" => NULL
            ]
        ]);

        $this->piRequestController->execute();
    }

    public function testExecuteERROR()
    {
        $this->quoteManagementMock->expects($this->any())
            ->method('submit')
            ->will($this->returnValue(NULL));

        $this->_expectResultJson([
            "success" => false,
            'error_message' => __("Something went wrong: Unable to save Sage Pay order.")
        ]);

        $this->piRequestController->execute();
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
