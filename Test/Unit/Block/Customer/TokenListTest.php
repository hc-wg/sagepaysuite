<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Block\Customer;

class TokenListTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Block\Customer\TokenList
     */
    private $tokenListBlock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('customer/account/');

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

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->tokenListBlock = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Block\Customer\TokenList',
            [
                "context" => $contextMock,
                "currentCustomer" => $currentCustomerMock,
                "config" => $configMock,
                "tokenModel" => $tokenModelMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testGetBackUrl()
    {
        $this->tokenListBlock->getBackUrl();
    }
}
