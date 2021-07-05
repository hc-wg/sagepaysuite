<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\PI;

use Ebizmarts\SagePaySuite\Controller\PI\Failure;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\TestCase;

class FailureTest extends TestCase
{
    private $configMock;

    private $onePageMock;

    private $quoteRepositoryMock;

    private $recoverCartMock;

    private $failureMock;

    private $contextMock;

    public function testExecuteCancelOrder()
    {
        $this->createConstructorMock();
        $errorMessage = "Invalid Opayo response: Transaction rejected by the fraud rules you have in place.";
        $params = [
            'quoteId' => 12,
            'orderId' => 10,
            'errorMessage' => urlencode($errorMessage)
        ];

        $sessionMock = $this
            ->getMockBuilder(Session::class)
            ->setMethods(['setData', 'setQuoteId', 'replaceQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->onePageMock
            ->expects($this->once())
            ->method('getCheckout')
            ->willReturn($sessionMock);

        $requestMock = $this
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->failureMock
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);
        $requestMock
            ->expects($this->once())
            ->method('getParams')
            ->willReturn($params);

        $this->recoverCartMock
            ->expects($this->once())
            ->method('setShouldCancelOrder')
            ->with(true)
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('setOrderId')
            ->with($params['orderId'])
            ->willReturnSelf();
        $this->recoverCartMock
            ->expects($this->once())
            ->method('execute');

        $sessionMock
            ->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                [SagePaySession::PRESAVED_PENDING_ORDER_KEY, null],
                [SagePaySession::CONVERTING_QUOTE_TO_ORDER, 0]
            );

        $this->failureMock
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with($params['errorMessage'])
            ->willReturnSelf();

        $this->failureMock
            ->expects($this->once())
            ->method('_redirect')
            ->with('checkout/cart');

        $this->failureMock->execute();
    }

    public function testExecuteSetQuoteToSession()
    {
        $this->createConstructorMock();
        $errorMessage = "Invalid Opayo response: Transaction rejected by the fraud rules you have in place.";
        $params = [
            'quoteId' => 12,
            'errorMessage' => urlencode($errorMessage)
        ];

        $sessionMock = $this
            ->getMockBuilder(Session::class)
            ->setMethods(['setData', 'setQuoteId', 'replaceQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->onePageMock
            ->expects($this->once())
            ->method('getCheckout')
            ->willReturn($sessionMock);

        $requestMock = $this
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->failureMock
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);
        $requestMock
            ->expects($this->once())
            ->method('getParams')
            ->willReturn($params);

        $sessionMock
            ->expects($this->once())
            ->method('setQuoteId')
            ->with($params['quoteId'])
            ->willReturnSelf();
        $quoteMock = $this
            ->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($params['quoteId'])
            ->willReturn($quoteMock);
        $sessionMock
            ->expects($this->once())
            ->method('replaceQuote')
            ->with($quoteMock)
            ->willReturnSelf();

        $sessionMock
            ->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                [SagePaySession::PRESAVED_PENDING_ORDER_KEY, null],
                [SagePaySession::CONVERTING_QUOTE_TO_ORDER, 0]
            );

        $this->failureMock
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with($params['errorMessage'])
            ->willReturnSelf();

        $this->failureMock
            ->expects($this->once())
            ->method('_redirect')
            ->with('checkout/cart');

        $this->failureMock->execute();
    }

    private function createConstructorMock()
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this
            ->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->onePageMock = $this
            ->getMockBuilder(Onepage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepositoryMock = $this
            ->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->recoverCartMock = $this
            ->getMockBuilder(RecoverCart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->failureMock = $this
            ->getMockBuilder(Failure::class)
            ->setMethods(['getRequest', '_redirect', 'addErrorMessage'])
            ->setConstructorArgs([
                'context'         => $this->contextMock,
                'onepage'         => $this->onePageMock,
                'config'          => $this->configMock,
                'quoteRepository' => $this->quoteRepositoryMock,
                'recoverCart'     => $this->recoverCartMock
            ])
            ->getMock();
    }
}
