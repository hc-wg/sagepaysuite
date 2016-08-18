<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Helper;

class CheckoutTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderSenderMock;


    protected function setUp()
    {
        $customerMock = $this
            ->getMockBuilder('Magento\Customer\Model\Customer')
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock->expects($this->any())
            ->method('getDefaultBilling')
            ->will($this->returnValue(0));

        $this->quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();

        $checkoutSessionMock = $this
            ->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));

        $this->customerSessionMock = $this
            ->getMockBuilder('Magento\Customer\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $customerRepositoryMock = $this
            ->getMockBuilder('Magento\Customer\Api\CustomerRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($customerMock));

        $quoteManagementMock = $this
            ->getMockBuilder('Magento\Quote\Model\QuoteManagement')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteManagementMock->expects($this->any())
            ->method('submit')
            ->will($this->returnValue($this->orderMock));

        $objectCopyServiceMock = $this
            ->getMockBuilder('Magento\Framework\DataObject\Copy')
            ->disableOriginalConstructor()
            ->getMock();
        $objectCopyServiceMock->expects($this->any())
            ->method('getDataFromFieldset')
            ->will($this->returnValue([]));

        $dataObjectHelperMock = $this
            ->getMockBuilder('Magento\Framework\Api\DataObjectHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $dataObjectHelperMock->expects($this->any())
            ->method('populateWithArray')
            ->will($this->returnValue([]));

        $this->orderSenderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Email\Sender\OrderSender')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->checkoutHelper = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Checkout',
            [
                'customerSession' => $this->customerSessionMock,
                'checkoutSession' => $checkoutSessionMock,
                'customerRepository' => $customerRepositoryMock,
                'quoteManagement' => $quoteManagementMock,
                'objectCopyService' => $objectCopyServiceMock,
                'dataObjectHelper' => $dataObjectHelperMock,
                'orderSender' => $this->orderSenderMock
            ]
        );
    }

    public function testPlaceOrderCUSTOMER()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue(true));

        $this->quoteMock->expects($this->once())
            ->method('isVirtual')
            ->will($this->returnValue(true));

        $addressInterfaceMock = $this
            ->getMockBuilder('Magento\Customer\Api\Data\AddressInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->once())
            ->method('getCustomerId')
            ->will($this->returnValue(null));
        $addressMock->expects($this->once())
            ->method('exportCustomerAddress')
            ->willReturn($addressInterfaceMock);

        $addressInterfaceMock->expects($this->once())
            ->method('setIsDefaultBilling');

        $this->quoteMock->expects($this->once())
            ->method('addCustomerAddress');

        $this->quoteMock->expects($this->once())
            ->method('getBillingAddress')
            ->will($this->returnValue($addressMock));

        $this->checkoutHelper->placeOrder();
    }

    public function testPlaceOrderGUEST()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue(false));

        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue("test@example.com"));

        $this->quoteMock->expects($this->any())
            ->method('getCheckoutMethod')
            ->will($this->returnValue(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST));
        $this->quoteMock->expects($this->once())
            ->method('getBillingAddress')
            ->will($this->returnValue($addressMock));

        $this->quoteMock->expects($this->once())
            ->method('setCustomerIsGuest')
            ->with(true);

        $this->checkoutHelper->placeOrder();
    }

    public function testPlaceOrderREGISTER()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue(false));

        $this->quoteMock->expects($this->once())
            ->method('isVirtual')
            ->will($this->returnValue(true));

        $addressInterfaceMock = $this
            ->getMockBuilder('Magento\Customer\Api\Data\AddressInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $addressInterfaceMock->expects($this->once())
            ->method('setIsDefaultShipping');

        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->once())
            ->method('exportCustomerAddress')
            ->will($this->returnValue($addressInterfaceMock));

        $this->quoteMock->expects($this->any())
            ->method('getCheckoutMethod')
            ->will($this->returnValue(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER));
        $this->quoteMock->expects($this->once())
            ->method('getBillingAddress')
            ->will($this->returnValue($addressMock));

        $this->quoteMock->expects($this->once())
            ->method('addCustomerAddress');

        $this->checkoutHelper->placeOrder();
    }

    public function testSendOrderEmail()
    {
        $this->orderSenderMock->expects($this->once())
            ->method('send');

        $this->checkoutHelper->sendOrderEmail($this->orderMock);
    }
}
