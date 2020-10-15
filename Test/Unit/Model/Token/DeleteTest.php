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
    /**
     * @dataProvider removeTokenDataProvider
     */
    public function testRemoveTokenFromVault($data)
    {
        $tokenId = 32;
        $customerId = $data['paramCustomerId'];

        $paymentTokenInterfaceMock = $this
            ->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenGetMock = $this
            ->getMockBuilder(Get::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenGetMock
            ->expects($this->once())
            ->method('getTokenById')
            ->with($tokenId)
            ->willReturn($paymentTokenInterfaceMock);

        $paymentTokenInterfaceMock
            ->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($data['tokenCustomerId']);

        $paymentTokenRepositoryMock = $this
            ->getMockBuilder(PaymentTokenRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenRepositoryMock
            ->expects($this->exactly($data['executePaymentTokenRepository']))
            ->method('delete')
            ->with($paymentTokenInterfaceMock)
            ->willReturn(true);

        $objectManagerHelper = new ObjectManager($this);

        /** @var Delete $tokenDelete */
        $tokenDelete = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\Delete',
            [
                'tokenGet'               => $tokenGetMock,
                'paymentTokenRepository' => $paymentTokenRepositoryMock
            ]
        );

        $this->assertEquals($data['expectedResult'], $tokenDelete->removeTokenFromVault($tokenId, $customerId));
    }

    public function removeTokenDataProvider()
    {
        return [
            'test OK' => [
                [
                    'paramCustomerId' => 2,
                    'tokenCustomerId' => 2,
                    'executePaymentTokenRepository' => 1,
                    'expectedResult' => true
                ]
            ],
            'test ERROR' => [
                [
                    'paramCustomerId' => 2,
                    'tokenCustomerId' => 3,
                    'executePaymentTokenRepository' => 0,
                    'expectedResult' => false
                ]
            ]
        ];
    }
}
