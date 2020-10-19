<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\Token;

use Ebizmarts\SagePaySuite\Api\Data\ResultInterface;
use Ebizmarts\SagePaySuite\Helper\RepositoryQuery;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Token\Get;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenSearchResultsInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use PHPUnit\Framework\TestCase;

class GetTest extends TestCase
{
    public function testGetTokenById()
    {
        $tokenId = 10;

        $paymentTokenMock = $this
            ->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTokenRepositoryMock = $this
            ->getMockBuilder(PaymentTokenRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with($tokenId)
            ->willReturn($paymentTokenMock);

        $objectManagerHelper = new ObjectManager($this);

        /** @var Get $tokenGet */
        $tokenGet = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\Get',
            [
                'paymentTokenRepository' => $paymentTokenRepositoryMock
            ]
        );

        $this->assertEquals($paymentTokenMock, $tokenGet->getTokenById($tokenId));
    }

    public function testGetTokensFromCustomer()
    {
        $customerId = 34;
        $customerIdFilter = [
            'field' => 'customer_id',
            'conditionType' => 'eq',
            'value' => $customerId
        ];

        $searchCriteriaMock = $this
            ->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryQueryMock = $this
            ->getMockBuilder(RepositoryQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repositoryQueryMock
            ->expects($this->once())
            ->method('buildSearchCriteriaWithAnd')
            ->with([$customerIdFilter])
            ->willReturn($searchCriteriaMock);

        $paymentTokenSearchResultMock = $this
            ->getMockBuilder(PaymentTokenSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTokenRepositoryMock = $this
            ->getMockBuilder(PaymentTokenRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenRepositoryMock
            ->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn([$paymentTokenSearchResultMock]);

        $objectManagerHelper = new ObjectManager($this);

        /** @var Get $tokenGet */
        $tokenGet = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\Get',
            [
                'repositoryQuery'        => $repositoryQueryMock,
                'paymentTokenRepository' => $paymentTokenRepositoryMock
            ]
        );

        $tokenGet->getTokensFromCustomer($customerId);
    }

    public function testGetTokensFromCustomerToShowOnGrid()
    {
        $customerId = 21;
        $tokenDetailsAsArray = [
            'type' => 'VI',
            'maskedCC' => '5559',
            'expirationDate' => '12/23'
        ];
        $tokenDetailsAsJson = '{"type":"VI","maskedCC":"5559","expirationDate":"12\/23"}';

        $paymentTokenSearchResultMock = $this
            ->getMockBuilder(PaymentTokenSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTokenMock = $this
            ->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenSearchResultMock
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$paymentTokenMock]);
        $paymentTokenMock
            ->expects($this->once())
            ->method('getIsActive')
            ->willReturn(true);
        $paymentTokenMock
            ->expects($this->once())
            ->method('getIsVisible')
            ->willReturn(true);
        $paymentTokenMock
            ->expects($this->once())
            ->method('getTokenDetails')
            ->willReturn($tokenDetailsAsJson);

        $jsonSerializerMock = $this
            ->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $jsonSerializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with($tokenDetailsAsJson)
            ->willReturn($tokenDetailsAsArray);

        $paymentTokenMock
            ->expects($this->once())
            ->method('getEntityId')
            ->willReturn(243);
        $paymentTokenMock
            ->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $expectedReturn = [
            [
                'id' => 243,
                'customer_id' => $customerId,
                'cc_last_4' => '5559',
                'cc_type' => 'VI',
                'cc_exp_month' => '12',
                'cc_exp_year' => '23'
            ]
        ];

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock = $this
            ->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenRepositoryMock = $this
            ->getMockBuilder(PaymentTokenRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repositoryQueryMock = $this
            ->getMockBuilder(RepositoryQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenGetMock = $this
            ->getMockBuilder(Get::class)
            ->setMethods(['getTokensFromCustomer'])
            ->setConstructorArgs([
                'suiteLogger'            => $suiteLoggerMock,
                'jsonSerializer'         => $jsonSerializerMock,
                'result'                 => $resultMock,
                'paymentTokenRepository' => $paymentTokenRepositoryMock,
                'repositoryQuery'        => $repositoryQueryMock
            ])
            ->getMock();
        $tokenGetMock
            ->expects($this->once())
            ->method('getTokensFromCustomer')
            ->with($customerId)
            ->willReturn($paymentTokenSearchResultMock);

        $this->assertEquals($expectedReturn, $tokenGetMock->getTokensFromCustomerToShowOnGrid($customerId));
    }

    public function testGetSagePayToken()
    {
        $tokenId = 21;
        $token = '04C9FEF1-9746-4C5E-A2C0-731355ED80C8';

        $paymentTokenMock = $this
            ->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTokenRepositoryMock = $this
            ->getMockBuilder(PaymentTokenRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with($tokenId)
            ->willReturn($paymentTokenMock);

        $paymentTokenMock
            ->expects($this->once())
            ->method('getGatewayToken')
            ->willReturn($token);

        $objectManagerHelper = new ObjectManager($this);

        /** @var Get $tokenGet */
        $tokenGet = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Token\Get',
            [
                'paymentTokenRepository' => $paymentTokenRepositoryMock
            ]
        );

        $this->assertEquals($token, $tokenGet->getSagePayToken($tokenId));
    }

    /**
     * @dataProvider getSagePayTokenAsResultInterfaceDataProvider
     */
    public function testGetSagePayTokenAsResultInterface($data)
    {
        $tokenId = 23;

        $resultMock = $this
            ->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock
            ->expects($this->once())
            ->method('setSuccess')
            ->with($data['setSuccessParam']);
        $resultMock
            ->expects($this->exactly($data['setResponseWillExecute']))
            ->method('setResponse')
            ->with($data['token']);

        $suiteLoggerMock = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $jsonSerializerMock = $this
            ->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenRepositoryMock = $this
            ->getMockBuilder(PaymentTokenRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repositoryQueryMock = $this
            ->getMockBuilder(RepositoryQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenGetMock = $this
            ->getMockBuilder(Get::class)
            ->setMethods(['getSagePayToken'])
            ->setConstructorArgs([
                'suiteLogger'            => $suiteLoggerMock,
                'jsonSerializer'         => $jsonSerializerMock,
                'result'                 => $resultMock,
                'paymentTokenRepository' => $paymentTokenRepositoryMock,
                'repositoryQuery'        => $repositoryQueryMock
            ])
            ->getMock();
        $tokenGetMock
            ->expects($this->once())
            ->method('getSagePayToken')
            ->with($tokenId)
            ->willReturn($data['token']);

        $tokenGetMock->getSagePayTokenAsResultInterface($tokenId);
    }

    public function getSagePayTokenAsResultInterfaceDataProvider()
    {
        return [
            'test Success' => [
                [
                    'token' => '04C9FEF1-9746-4C5E-A2C0-731355ED80C8',
                    'setSuccessParam' => true,
                    'setResponseWillExecute' => 1
                ]
            ],
            'test Error' => [
                [
                    'token' => '',
                    'setSuccessParam' => false,
                    'setResponseWillExecute' => 0
                ]
            ]
        ];
    }
}
