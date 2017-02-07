<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\PI;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    private $contextMock;
    private $configMock;
    private $suiteHelperMock;
    private $pirestapiMock;
    private $requestHelperMock;
    private $paymentMock;
    private $ccConverterObj;
    private $piRequestMock;

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var \Ebizmarts\SagePaySuite\Controller\PI\Request
     */
    private $piRequestController;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutHelperMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultJson;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $piModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\PI')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')->disableOriginalConstructor()->getMock();
        $this->paymentMock->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($piModelMock);

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
            ->will($this->returnValue($this->paymentMock));
        $quoteMock->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($addressMock));
        $quoteMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($addressMock);

        $this->checkoutSessionMock = $this
            ->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock->expects($this->any())
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

        $this->contextMock = $this->getMockBuilder('Magento\Framework\App\Action\Context')->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue(
                'Content-Language: en-GB' . PHP_EOL . PHP_EOL .
                '{"merchant_session_key": "12345", "card_identifier":"12345", ' .
                '"card_last4":"0006", "card_exp_month":"02", "card_exp_year":"22", ' .
                '"card_type":"Visa"}'
            ));
        $this->contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->will($this->returnValue($resultFactoryMock));

        $this->configMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')->disableOriginalConstructor()->getMock();

        $this->suiteHelperMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')->disableOriginalConstructor()->getMock();
        $this->suiteHelperMock->expects($this->any())
            ->method('generateVendorTxCode')
            ->will($this->returnValue("10000001-2015-12-12-12-12345"));

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));
        $this->orderMock->expects($this->any())
            ->method('place')
            ->willReturnSelf();

        $this->checkoutHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Checkout')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestHelperMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Request')->disableOriginalConstructor()->getMock();
        $this->requestHelperMock->expects($this->any())
            ->method('populatePaymentAmount')
            ->will($this->returnValue([]));
        $this->requestHelperMock->expects($this->any())
            ->method('getOrderDescription')
            ->will($this->returnValue("description"));

        $this->piRequestMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PiRequest::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequestData'])
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        /** @var \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType $ccConverterObj */
        $this->ccConverterObj = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Config\SagePayCardType',
            []
        );
    }
    // @codingStandardsIgnoreEnd

    public function testExecuteSUCCESS()
    {
        $this->markTestSkipped('The PI request controller does not exist. Move this test.');

        $threedStatusObj = new \stdClass();
        $threedStatusObj->status = "NotChecked";

        $captureObj = new \stdClass();
        $captureObj->statusCode = \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS;
        $captureObj->transactionId = self::TEST_VPSTXID;
        $captureObj->statusDetail = 'OK Status';
        $captureObj->{"3DSecure"} = $threedStatusObj;

        $this->pirestapiMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')->disableOriginalConstructor()->getMock();
        $this->pirestapiMock->expects($this->any())
            ->method('capture')
            ->willReturn($captureObj);

        $this->checkoutHelperMock->expects($this->once())->method('placeOrder')->willReturn($this->orderMock);

        $this->paymentMock->expects($this->once())->method('setMethod')->with('sagepaysuitepi');
        $this->paymentMock->expects($this->exactly(2))->method('setTransactionId')->with('F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F');
        $this->paymentMock->expects($this->once())->method('setLastTransId')->with('F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F');
        $this->paymentMock->expects($this->once())->method('setCcLast4')->with('0006');
        $this->paymentMock->expects($this->once())->method('setCcExpMonth')->with('02');
        $this->paymentMock->expects($this->once())->method('setCcExpYear')->with('22');
        $this->paymentMock->expects($this->once())->method('setCcType')->with('VI');

        $threedStatus = new \stdClass();
        $threedStatus->status = "NotChecked";

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->piRequestController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\Request',
            [
                'context'         => $this->contextMock,
                'config'          => $this->configMock,
                'suiteHelper'     => $this->suiteHelperMock,
                'pirestapi'       => $this->pirestapiMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'checkoutHelper'  => $this->checkoutHelperMock,
                'requestHelper'   => $this->requestHelperMock,
                'ccConverter'     => $this->ccConverterObj,
                'piRequest'       => $this->piRequestMock
            ]
        );

        $this->_expectResultJson([
            "success" => true,
            'response' => [
                "statusCode"    => 0000,
                "transactionId" => self::TEST_VPSTXID,
                "statusDetail"  => "OK Status",
                "3DSecure"      => $threedStatus,
                "orderId"       => null,
                "quoteId"       => null
            ]
        ]);

        $this->piRequestController->execute();
    }

    public function testExecuteSUCCESSDropin()
    {
        $this->markTestSkipped('The PI request controller does not exist. Move this test.');

        $threedStatusObj = new \stdClass();
        $threedStatusObj->status = "NotChecked";

        $cardMethod = new \stdClass();
        $cardMethod->cardType       = "AmericanExpress";
        $cardMethod->lastFourDigits = "0004";
        $cardMethod->expiryDate     = "1219";
        $paymentMethod = new \stdClass();
        $paymentMethod->card = $cardMethod;
        $captureObj = new \stdClass();
        $captureObj->paymentMethod = $paymentMethod;
        $captureObj->statusCode = \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS;
        $captureObj->transactionId = self::TEST_VPSTXID;
        $captureObj->statusDetail = 'OK Status';
        $captureObj->{"3DSecure"} = $threedStatusObj;

        $this->pirestapiMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')->disableOriginalConstructor()->getMock();
        $this->pirestapiMock->expects($this->any())
            ->method('capture')
            ->willReturn($captureObj);

        $this->checkoutHelperMock->expects($this->once())->method('placeOrder')->willReturn($this->orderMock);

        $this->paymentMock->expects($this->once())->method('setMethod')->with('sagepaysuitepi');
        $this->paymentMock->expects($this->exactly(2))->method('setTransactionId')->with('F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F');
        $this->paymentMock->expects($this->once())->method('setLastTransId')->with('F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F');
        $this->paymentMock->expects($this->once())->method('setCcLast4')->with('0004');
        $this->paymentMock->expects($this->once())->method('setCcExpMonth')->with('12');
        $this->paymentMock->expects($this->once())->method('setCcExpYear')->with('19');
        $this->paymentMock->expects($this->once())->method('setCcType')->with('AE');

        $threedStatus = new \stdClass();
        $threedStatus->status = "NotChecked";

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->piRequestController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\Request',
            [
                'context'         => $this->contextMock,
                'config'          => $this->configMock,
                'suiteHelper'     => $this->suiteHelperMock,
                'pirestapi'       => $this->pirestapiMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'checkoutHelper'  => $this->checkoutHelperMock,
                'requestHelper'   => $this->requestHelperMock,
                'ccConverter'     => $this->ccConverterObj,
                'piRequest'       => $this->piRequestMock
            ]
        );

        $this->_expectResultJson([
            "success" => true,
            'response' => [
                "statusCode"    => '0000',
                "transactionId" => self::TEST_VPSTXID,
                "statusDetail"  => "OK Status",
                "3DSecure"      => $threedStatus,
                "orderId"       => null,
                "quoteId"       => null,
                "paymentMethod" => $paymentMethod
            ]
        ]);

        $this->piRequestController->execute();
    }

    public function testExecuteERROR()
    {
        $this->markTestSkipped('The PI request controller does not exist. Move this test.');

        $threedStatusObj = new \stdClass();
        $threedStatusObj->status = "NotChecked";

        $captureObj = new \stdClass();
        $captureObj->statusCode = \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS;
        $captureObj->transactionId = self::TEST_VPSTXID;
        $captureObj->statusDetail = 'OK Status';
        $captureObj->{"3DSecure"} = $threedStatusObj;

        $this->pirestapiMock = $this->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\PIRest')->disableOriginalConstructor()->getMock();
        $this->pirestapiMock->expects($this->any())
            ->method('capture')
            ->willReturn($captureObj);

        $this->checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue(null));

        $this->_expectResultJson([
            "success" => false,
            'error_message' => "Something went wrong: Unable to save Sage Pay order"
        ]);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->piRequestController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\PI\Request',
            [
                'context'         => $this->contextMock,
                'config'          => $this->configMock,
                'suiteHelper'     => $this->suiteHelperMock,
                'pirestapi'       => $this->pirestapiMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'checkoutHelper'  => $this->checkoutHelperMock,
                'requestHelper'   => $this->requestHelperMock,
                'ccConverter'     => $this->ccConverterObj,
                'piRequest'       => $this->piRequestMock
            ]
        );

        $this->piRequestController->execute();
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
