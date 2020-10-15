<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Token;

use Ebizmarts\SagePaySuite\Model\Token\Delete;
use Ebizmarts\SagePaySuite\Model\Token\Get;
use Ebizmarts\SagePaySuite\Model\Token\Save;
use Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler;
use Ebizmarts\SagePaySuite\Plugin\DeleteTokenFromSagePay;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order\Payment;
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

    public function testDeleteToken()
    {
        $tokenId = 34;
        $customerId = 5;
        $token = '04C9FEF1-9746-4C5E-A2C0-731355ED80C8';

        $tokenGetMock = $this
            ->getMockBuilder(Get::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenGetMock
            ->expects($this->once())
            ->method('getSagePayToken')
            ->with($tokenId)
            ->willReturn($token);

        $deleteTokenFromSagePayMock = $this
            ->getMockBuilder(DeleteTokenFromSagePay::class)
            ->disableOriginalConstructor()
            ->getMock();
        $deleteTokenFromSagePayMock
            ->expects($this->once())
            ->method('deleteFromSagePay')
            ->with($token);

        $tokenDeleteMock = $this
            ->getMockBuilder(Delete::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenDeleteMock
            ->expects($this->once())
            ->method('removeTokenFromVault')
            ->with($tokenId, $customerId)
            ->willReturn(true);

        $objectManagerHelper = new ObjectManager($this);

        /** @var VaultDetailsHandler $vaultDetailsHandler */
        $vaultDetailsHandler = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler',
            [
                'tokenGet'               => $tokenGetMock,
                'tokenDelete'            => $tokenDeleteMock,
                'deleteTokenFromSagePay' => $deleteTokenFromSagePayMock
            ]
        );

        $this->assertEquals(true, $vaultDetailsHandler->deleteToken($tokenId, $customerId));
    }
}
