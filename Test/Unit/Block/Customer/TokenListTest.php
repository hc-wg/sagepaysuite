<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Customer;

class TokenListTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeTokenModelMock()
    {
        $tokenModelMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Token::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(["saveToken"])
            ->getMock();

        return $tokenModelMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeConfigMock()
    {
        $configMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $configMock;
    }

    /**
     * @param $urlBuilderMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeContextMockWithUrlBuilder($urlBuilderMock)
    {
        $contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\Template\Context::class)
            ->setMethods(["getUrlBuilder"])->disableOriginalConstructor()->getMock();
        $contextMock->expects($this->any())->method('getUrlBuilder')->will($this->returnValue($urlBuilderMock));

        return $contextMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeCurrentCustomerMock()
    {
        $currentCustomerMock = $this->getMockBuilder('Magento\Customer\Helper\Session\CurrentCustomer')
            ->setMethods(["getCustomerId"])->disableOriginalConstructor()->getMock();
        $currentCustomerMock->expects($this->any())->method('getCustomerId')->will($this->returnValue(1));

        return $currentCustomerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeUrlBuilderMockWithGetUrl()
    {
        $urlBuilderMock = $this->getMockBuilder(\Magento\Framework\Url::class)
            ->setMethods(["getUrl"])->disableOriginalConstructor()->getMock();

        return $urlBuilderMock;
    }

    public function testGetMaxTokenPerCustomer()
    {
        $configMock = $this->makeConfigMock();
        $configMock
            ->expects($this->once())
            ->method("getMaxTokenPerCustomer")
            ->willReturn(3);

        $this->assertEquals(3, $configMock->getMaxTokenPerCustomer());
    }
}
