<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Form;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class SuccessTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * @var /Ebizmarts\SagePaySuite\Controller\Form\Success
     */
    private $formSuccessController;

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
     * @var  Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var Ebizmarts\SagePaySuite\Helper\Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutHelperMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
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

        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

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
            ->method('getRedirect')
            ->will($this->returnValue($this->redirectMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

        $transactionMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\Transaction')
            ->disableOriginalConstructor()
            ->getMock();

        $transactionFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment\TransactionFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $transactionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($transactionMock));

        $this->checkoutHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Checkout')
            ->disableOriginalConstructor()
            ->getMock();

        $formModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $formModelMock->expects($this->any())
            ->method('decodeSagePayResponse')
            ->will($this->returnValue([
                "VPSTxId" => "{" . self::TEST_VPSTXID . "}",
                "CardType" => "VISA",
                "Last4Digits" => "0006",
                "StatusDetail" => "OK_STATUS_DETAIL",
                "VendorTxCode" => "100000001-2016-12-12-12346789"
            ]));

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->formSuccessController = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\Success',
            [
                'context' => $contextMock,
                'config' => $configMock,
                'checkoutSession' => $checkoutSessionMock,
                'checkoutHelper' => $this->checkoutHelperMock,
                'transactionFactory' => $transactionFactoryMock,
                'formModel' => $formModelMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testExecuteSUCCESS()
    {
        $this->checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue($this->orderMock));

        $this->_expectRedirect("checkout/onepage/success");
        $this->formSuccessController->execute();
    }

    public function testExecuteERROR()
    {
        $this->checkoutHelperMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue(null));

        $this->_expectRedirect("checkout/cart");
        $this->formSuccessController->execute();
    }

    /**
     * @param string $path
     */
    private function _expectRedirect($path)
    {
        $this->redirectMock->expects($this->once())
            ->method('redirect')
            ->with($this->anything(), $path, []);
    }
}
