<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Token\Save;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    private $paymentMock;

    private $jsonSerializerMock;

    public function testSaveToken()
    {
        $customerId = 2;
        $token = '04C9FEF1-9746-4C5E-A2C0-731355ED80C8';
        $this->paymentMock = $this
            ->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenFactoryMock = $this
            ->getMockBuilder(PaymentTokenFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenMock = $this
            ->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTokenFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD)
            ->willReturn($paymentTokenMock);

        $paymentTokenMock
            ->expects($this->once())
            ->method('setGatewayToken')
            ->with($token);
        $paymentTokenMock
            ->expects($this->once())
            ->method('setTokenDetails')
            ->with($this->createTokenDetails());
        $paymentTokenMock
            ->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId);
        $paymentMethod = 'sagepaysuitepi';
        $this->paymentMock
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn($paymentMethod);
        $paymentTokenMock
            ->expects($this->once())
            ->method('setPaymentMethodCode')
            ->with();
        $publicHash = 'bafc93f2af0b9e076682e64e24fc18ff';
        $paymentTokenMock
            ->expects($this->once())
            ->method('setPublicHash')
            ->with($publicHash);
        $paymentTokenMock
            ->expects($this->once())
            ->method('setIsVisible')
            ->with(true);
        $paymentTokenMock
            ->expects($this->once())
            ->method('setIsActive')
            ->with(true);

        $paymentTokenRepositoryMock = $this
            ->getMockBuilder(PaymentTokenRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($paymentTokenMock);

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        /** @var Save $tokenSave */
        $tokenSave = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\Save',
            [
                'suiteLogger'            => $suiteLoggerMock,
                'paymentTokenFactory'    => $paymentTokenFactoryMock,
                'jsonSerializer'         => $this->jsonSerializerMock,
                'paymentTokenRepository' => $paymentTokenRepositoryMock
            ]
        );

        $tokenSave->saveToken($this->paymentMock, $customerId, $token);
    }

    /**
     * @return string
     */
    private function createTokenDetails()
    {
        $ccType = 'VI';
        $ccLast4 = '5559';
        $ccExpMonth = 12;
        $ccExpYear = 23;

        $this->paymentMock
            ->expects($this->once())
            ->method('getCcType')
            ->willReturn($ccType);
        $this->paymentMock
            ->expects($this->once())
            ->method('getCcLast4')
            ->willReturn($ccLast4);
        $this->paymentMock
            ->expects($this->once())
            ->method('getCcExpMonth')
            ->willReturn($ccExpMonth);
        $this->paymentMock
            ->expects($this->once())
            ->method('getCcExpYear')
            ->willReturn($ccExpYear);

        $tokenDetailsAsArray = [
            'type' => $ccType,
            'maskedCC' => $ccLast4,
            'expirationDate' => $ccExpMonth . '/' . $ccExpYear
        ];
        $tokenDetailsAsString = '{"type":"VI","maskedCC":"5559","expirationDate":"12\/23"}';

        $this->jsonSerializerMock = $this
            ->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonSerializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($tokenDetailsAsArray)
            ->willReturn($tokenDetailsAsString);

        return $tokenDetailsAsString;
    }
}
