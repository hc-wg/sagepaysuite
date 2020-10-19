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

    private $customerId = 23;

    public function testGetBackUrl()
    {
        $vaultToken = [
            'id' => 23,
            'customer_id' => $this->customerId,
            'cc_last_4' => '5559',
            'cc_type' => 'VI',
            'cc_exp_month' => '12',
            'cc_exp_year' => '23'
        ];
        $serverToken = [
            'id' => 543,
            'customer_id' => $this->customerId,
            'cc_last_4' => '0006',
            'cc_type' => 'VI',
            'cc_exp_month' => '04',
            'cc_exp_year' => '22'
        ];

        $urlBuilderMock = $this->makeUrlBuilderMockWithGetUrl();
        $urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('customer/account/')
            ->willReturn('customer/account/');

        $tokenModelMock = $this->makeTokenModelMock();
        $tokenModelMock
            ->expects($this->once())
            ->method('getCustomerTokens')
            ->willReturn([$serverToken]);

        $vaultDetailsHandlerMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $vaultDetailsHandlerMock
            ->expects($this->once())
            ->method('getTokensFromCustomerToShowOnGrid')
            ->with($this->customerId)
            ->willReturn([$vaultToken]);

        $this->tokenListBlock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Block\Customer\TokenList::class)
            ->setMethods(['setItems', 'getRefererUrl'])
            ->setConstructorArgs(
                [
                    "context"             => $this->makeContextMockWithUrlBuilder($urlBuilderMock),
                    "currentCustomer"     => $this->makeCurrentCustomerMock(),
                    "config"              => $this->makeConfigMock(),
                    "vaultDetailsHandler" => $vaultDetailsHandlerMock,
                    "tokenModel"          => $tokenModelMock
                ]
            )
            ->getMock();

        $this->tokenListBlock->expects($this->once())->method('getRefererUrl')->willReturn(null);

        $url = $this->tokenListBlock->getBackUrl();

        $this->assertEquals('customer/account/', $url);
    }

    public function testGetBackUrlReferrer()
    {
        $vaultToken = [
            'id' => 23,
            'customer_id' => $this->customerId,
            'cc_last_4' => '5559',
            'cc_type' => 'VI',
            'cc_exp_month' => '12',
            'cc_exp_year' => '23'
        ];

        $urlBuilderMock = $this->makeUrlBuilderMockWithGetUrl();
        $urlBuilderMock->expects($this->never())
            ->method('getUrl');

        $tokenModelMock = $this->makeTokenModelMock();
        $tokenModelMock->expects($this->any())
            ->method('getCustomerTokens')
            ->will($this->returnValue([]));

        $vaultDetailsHandlerMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $vaultDetailsHandlerMock
            ->expects($this->once())
            ->method('getTokensFromCustomerToShowOnGrid')
            ->with($this->customerId)
            ->willReturn([$vaultToken]);

        $this->tokenListBlock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Block\Customer\TokenList::class)
            ->setMethods(['setItems', 'getRefererUrl'])
            ->setConstructorArgs(
                [
                    "context"             => $this->makeContextMockWithUrlBuilder($urlBuilderMock),
                    "currentCustomer"     => $this->makeCurrentCustomerMock(),
                    "config"              => $this->makeConfigMock(),
                    "vaultDetailsHandler" => $vaultDetailsHandlerMock,
                    "tokenModel"          => $tokenModelMock
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
    private function makeCurrentCustomerMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        $currentCustomerMock = $this->getMockBuilder('Magento\Customer\Helper\Session\CurrentCustomer')
            ->setMethods(["getCustomerId"])->disableOriginalConstructor()->getMock();
        $currentCustomerMock
            ->expects($this->exactly(2))
            ->method('getCustomerId')
            ->willReturn($this->customerId);

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
