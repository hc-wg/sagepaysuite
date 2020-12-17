<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Token\Delete;
use Ebizmarts\SagePaySuite\Model\Token\Get;
use Ebizmarts\SagePaySuite\Model\Token\Save;
use Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler;
use Ebizmarts\SagePaySuite\Plugin\DeleteTokenFromSagePay;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PHPUnit\Framework\TestCase;

class VaultDetailsHandlerTest extends TestCase
{
    public function testSaveToken()
    {
        $customerId = 2;
        $token = '04C9FEF1-9746-4C5E-A2C0-731355ED80C8';
        $paymentMock = $this
            ->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenSaveMock = $this
            ->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenSaveMock
            ->expects($this->once())
            ->method('saveToken')
            ->with($paymentMock, $customerId, $token);

        $objectManagerHelper = new ObjectManager($this);

        /** @var VaultDetailsHandler $vaultDetailsHandler */
        $vaultDetailsHandler = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler',
            [
                'tokenSave' => $tokenSaveMock
            ]
        );

        $vaultDetailsHandler->saveToken($paymentMock, $customerId, $token);
    }

    public function testGetTokensFromCustomerToShowOnGrid()
    {
        $customerId = 2;
        $tokensToShowOnGrid = [
            [
                'id' => 1,
                'customer_id' => $customerId,
                'cc_last_4' => '5559',
                'cc_type' => 'VI',
                'cc_exp_month' => '12',
                'cc_exp_year' => '23'
            ]
        ];

        $tokenGetMock = $this
            ->getMockBuilder(Get::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenGetMock
            ->expects($this->once())
            ->method('getTokensFromCustomerToShowOnGrid')
            ->with($customerId)
            ->willReturn($tokensToShowOnGrid);

        $objectManagerHelper = new ObjectManager($this);

        /** @var VaultDetailsHandler $vaultDetailsHandler */
        $vaultDetailsHandler = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler',
            [
                'tokenGet' => $tokenGetMock
            ]
        );

        $this->assertEquals($tokensToShowOnGrid, $vaultDetailsHandler->getTokensFromCustomerToShowOnGrid($customerId));
    }

    /**
     * @dataProvider deleteTokenTestDataProvider
     */
    public function testDeleteToken($data)
    {
        $tokenId = 34;
        $sagepayToken = '04C9FEF1-9746-4C5E-A2C0-731355ED80C8';

        $tokenMock = $this
            ->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenMock
            ->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($data['tokenCustomerId']);
        $tokenMock
            ->expects($this->exactly($data['executeGetGatewayToken']))
            ->method('getGatewayToken')
            ->willReturn($sagepayToken);

        $tokenGetMock = $this
            ->getMockBuilder(Get::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenGetMock
            ->expects($this->once())
            ->method('getTokenById')
            ->with($tokenId)
            ->willReturn($tokenMock);

        $deleteTokenFromSagePayMock = $this
            ->getMockBuilder(DeleteTokenFromSagePay::class)
            ->disableOriginalConstructor()
            ->getMock();
        $deleteTokenFromSagePayMock
            ->expects($this->exactly($data['executeDeleteToken']))
            ->method('deleteFromSagePay')
            ->with($sagepayToken);

        $tokenDeleteMock = $this
            ->getMockBuilder(Delete::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenDeleteMock
            ->expects($this->exactly($data['executeDeleteToken']))
            ->method('removeTokenFromVault')
            ->with($tokenMock)
            ->willReturn(true);

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteLoggerMock
            ->expects($this->exactly($data['executeLogException']))
            ->method('logException')
            ->with(
                new AuthenticationException(__('Unable to delete token from Opayo: customer does not own the token'))
            );

        $objectManagerHelper = new ObjectManager($this);

        /** @var VaultDetailsHandler $vaultDetailsHandler */
        $vaultDetailsHandler = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler',
            [
                'suiteLogger'            => $suiteLoggerMock,
                'tokenGet'               => $tokenGetMock,
                'tokenDelete'            => $tokenDeleteMock,
                'deleteTokenFromSagePay' => $deleteTokenFromSagePayMock
            ]
        );

        $this->assertEquals(
            $data['expectedResult'],
            $vaultDetailsHandler->deleteToken($tokenId, $data['paramCustomerId'])
        );
    }

    /**
     * @return array
     */
    public function deleteTokenTestDataProvider()
    {
        return [
            'test OK' => [
                [
                    'paramCustomerId' => 2,
                    'tokenCustomerId' => 2,
                    'executeDeleteToken' => 1,
                    'executeLogException' => 0,
                    'executeGetGatewayToken' => 1,
                    'expectedResult' => true
                ]
            ],
            'test ERROR customers are different' => [
                [
                    'paramCustomerId' => 2,
                    'tokenCustomerId' => 3,
                    'executeDeleteToken' => 0,
                    'executeLogException' => 1,
                    'executeGetGatewayToken' => 0,
                    'expectedResult' => false
                ]
            ]
        ];
    }
}
