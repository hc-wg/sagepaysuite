<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Token;

use Ebizmarts\SagePaySuite\Model\Token\Delete;
use Ebizmarts\SagePaySuite\Model\Token\Get;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    public function testRemoveTokenFromVault()
    {
        $paymentTokenInterfaceMock = $this
            ->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTokenRepositoryMock = $this
            ->getMockBuilder(PaymentTokenRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenRepositoryMock
            ->expects($this->once())
            ->method('delete')
            ->with($paymentTokenInterfaceMock)
            ->willReturn(true);

        $objectManagerHelper = new ObjectManager($this);

        /** @var Delete $tokenDelete */
        $tokenDelete = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\Delete',
            [
                'paymentTokenRepository' => $paymentTokenRepositoryMock
            ]
        );

        $this->assertEquals(true, $tokenDelete->removeTokenFromVault($paymentTokenInterfaceMock));
    }
}
