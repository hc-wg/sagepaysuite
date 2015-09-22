<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Form;

use Ebizmarts\SagePaySuite\Controller\Form\PlaceOrder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Model\Quote;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\IframeConfigProvider;
use Magento\Quote\Api\CartManagementInterface;

/**
 * Class PlaceOrderTest
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;
    /**
     * @var Place
     */
    protected $placeOrderController;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var DataFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataFactoryMock;

    /**
     * @var CartManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartManagementMock;

    /**
     * @var Onepage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $onepageCheckout;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonHelperMock;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var DirectpostSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $directpostSessionMock;

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;

    /**
     * @var CheckoutSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock;

    public function setUp()
    {
        $this->quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this
            ->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->objectManagerMock = $this
            ->getMockBuilder('Magento\Framework\ObjectManagerInterface')
            ->getMockForAbstractClass();
        $this->objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['Magento\Checkout\Model\Session', $this->checkoutSessionMock],
            ]);
        $this->coreRegistryMock = $this
            ->getMockBuilder('Magento\Framework\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartManagementMock = $this
            ->getMockBuilder('Magento\Quote\Api\CartManagementInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->onepageCheckout = $this
            ->getMockBuilder('Magento\Checkout\Model\Type\Onepage')
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonHelperMock = $this
            ->getMockBuilder('Magento\Framework\Json\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this
            ->getMockBuilder('Magento\Framework\App\RequestInterface')
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->placeOrderController = $this->objectManager->getObject(
            'Ebizmarts\SagePaySuite\Controller\Form\PlaceOrder',
            [
                'request' => $this->requestMock,
                'response' => $this->responseMock,
                'objectManager' => $this->objectManagerMock,
                'coreRegistry' => $this->coreRegistryMock,
                'dataFactory' => $this->dataFactoryMock,
                'cartManagement' => $this->cartManagementMock,
                'onepageCheckout' => $this->onepageCheckout,
                'jsonHelper' => $this->jsonHelperMock,
            ]
        );
    }

    /**
     * @param $paymentMethod
     * @param $controller
     * @param $quoteId
     * @param $orderId
     * @param $result
     * @dataProvider textExecuteDataProvider
     */
    public function testExecute(
        $paymentMethod,
        $controller,
        $quoteId,
        $orderId,
        $result
    ) {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('payment')
            ->will($this->returnValue($paymentMethod));

        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('controller')
            ->will($this->returnValue($controller));

        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($quoteId));

        $this->cartManagementMock->expects($this->any())
            ->method('placeOrder')
            ->will($this->returnValue($orderId));

        $this->jsonHelperMock->expects($this->any())
            ->method('jsonEncode')
            ->with($result);

        $this->placeOrderController->execute();
    }

    /**
     * @param $paymentMethod
     * @param $controller
     * @param $quoteId
     * @param $result
     * @dataProvider textExecuteFailedPlaceOrderDataProvider
     */
    public function testExecuteFailedPlaceOrder(
        $paymentMethod,
        $controller,
        $quoteId,
        $result
    ) {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('payment')
            ->will($this->returnValue($paymentMethod));

        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('controller')
            ->will($this->returnValue($controller));

        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($quoteId));

        $this->cartManagementMock->expects($this->once())
            ->method('placeOrder')
            ->willThrowException(new \Exception());

        $this->jsonHelperMock->expects($this->any())
            ->method('jsonEncode')
            ->with($result);

        $this->placeOrderController->execute();
    }

    /**
     * @return array
     */
    public function textExecuteDataProvider()
    {
        $objectSuccess = new \Magento\Framework\Object();
        $objectSuccess->setData('success', true);

        return [
            [
                ['method' => null],
                IframeConfigProvider::CHECKOUT_IDENTIFIER,
                1,
                1,
                ['error_messages' => __('Please choose a payment method.'), 'goto_section' => 'payment']
            ],
            [
                ['method' => 'sagepaysuiteform'],
                IframeConfigProvider::CHECKOUT_IDENTIFIER,
                1,
                1,
                $objectSuccess
            ],
        ];
    }

    /**
     * @return array
     */
    public function textExecuteFailedPlaceOrderDataProvider()
    {
        $objectFailed = new \Magento\Framework\Object();
        $objectFailed->setData('error', true);
        $objectFailed->setData('error_messages', __('Cannot place order.'));

        return [
            [
                ['method' => 'sagepaysuiteform'],
                IframeConfigProvider::CHECKOUT_IDENTIFIER,
                1,
                $objectFailed
            ],
        ];
    }
}
