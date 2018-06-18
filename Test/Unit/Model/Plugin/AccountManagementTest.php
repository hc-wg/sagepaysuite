<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Plugin\AccountManagement
     */
    private $pluginAccountManagementModel;

    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $quoteFactoryMock = $this
            ->getMockBuilder('Magento\Quote\Model\QuoteFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->quoteMock));

        $checkoutSessionMock = $this
            ->getMockBuilder('Magento\Checkout\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock->expects($this->any())
            ->method('getQuoteId')
            ->will($this->returnValue(1));

        $objectManagerHelper = new ObjectManager($this);
        $this->pluginAccountManagementModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Plugin\AccountManagement',
            [
                "quoteFactory" => $quoteFactoryMock,
                "checkoutSession" => $checkoutSessionMock
            ]
        );
    }
    // @codingStandardsIgnoreEnd

    public function testAroundIsEmailAvailable()
    {
        $proceed = $this->getProceedClosure(true, "test@example.com", null);

        $accountManagementMock = $this
            ->getMockBuilder('Magento\Customer\Model\AccountManagement')
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock->expects($this->once())
            ->method('save');

        $this->pluginAccountManagementModel->aroundIsEmailAvailable(
            $accountManagementMock,
            $proceed,
            "test@example.com",
            null
        );
    }

    private function getProceedClosure($result, $email, $storeId)
    {
        $self = $this;
        return function ($parameter1, $parameter2) use ($result, $email, $storeId, $self) {
            $self->assertSame($email, $parameter1);
            $self->assertSame($storeId, $parameter2);
            return $result;
        };
    }
}
