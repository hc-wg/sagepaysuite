<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Customer;

class TokenListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Customer\TokenList|\PHPUnit_Framework_MockObject_MockObject
     * ads|adsads
     * ]áds
     */
    private $tokenListBlock;

    public function testGetBackUrl()
    {
        $urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('customer/account/')
            ->willReturn('customer/account/');

        $contextMock = $this->getMockBuilder('Magento\Framework\View\Element\Template\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getUrlBuilder')
            ->will($this->returnValue($urlBuilderMock));

        $currentCustomerMock = $this
            ->getMockBuilder('Magento\Customer\Helper\Session\CurrentCustomer')
            ->disableOriginalConstructor()
            ->getMock();
        $currentCustomerMock->expects($this->any())
            ->method('getCustomerId')
            ->will($this->returnValue(1));

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $tokenModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Token')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getCustomerTokens')
            ->will($this->returnValue([]));

        $this->tokenListBlock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Block\Customer\TokenList::class)
            ->setMethods(['setItems', 'getRefererUrl'])
            ->setConstructorArgs(
                [
                    "context"         => $contextMock,
                    "currentCustomer" => $currentCustomerMock,
                    "config"          => $configMock,
                    "tokenModel"      => $tokenModelMock
                ]
            )
            ->getMock();

        $this->tokenListBlock->expects($this->once())->method('getRefererUrl')->willReturn(null);

        $url = $this->tokenListBlock->getBackUrl();

        $this->assertEquals('customer/account/', $url);
    }

    public function testGetBackUrlReferrer()
    {
        $urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $urlBuilderMock->expects($this->never())
            ->method('getUrl');

        $contextMock = $this->getMockBuilder('Magento\Framework\View\Element\Template\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getUrlBuilder')
            ->will($this->returnValue($urlBuilderMock));

        $currentCustomerMock = $this
            ->getMockBuilder('Magento\Customer\Helper\Session\CurrentCustomer')
            ->disableOriginalConstructor()
            ->getMock();
        $currentCustomerMock->expects($this->any())
            ->method('getCustomerId')
            ->will($this->returnValue(1));

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $tokenModelMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Token')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getCustomerTokens')
            ->will($this->returnValue([]));

        $this->tokenListBlock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Block\Customer\TokenList::class)
            ->setMethods(['setItems', 'getRefererUrl'])
            ->setConstructorArgs(
                [
                    "context"         => $contextMock,
                    "currentCustomer" => $currentCustomerMock,
                    "config"          => $configMock,
                    "tokenModel"      => $tokenModelMock
                ]
            )
            ->getMock();

        $this->tokenListBlock->expects($this->exactly(2))->method('getRefererUrl')->willReturn('category/men.html');

        $url = $this->tokenListBlock->getBackUrl();

        $this->assertEquals('category/men.html', $url);
    }
}
