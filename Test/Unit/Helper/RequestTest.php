<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Helper;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    protected $requestHelper;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->requestHelper = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Request',
            []
        );
    }

    /**
     * @dataProvider populateAddressInformationDataProvider
     */
    public function testPopulateAddressInformation($data)
    {
        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock->expects($this->any())
            ->method('getLastname')
            ->will($this->returnValue($data["lastname"]));
        $addressMock->expects($this->any())
            ->method('getFirstname')
            ->will($this->returnValue($data["firstname"]));
        $addressMock->expects($this->any())
            ->method('getStreetLine')
            ->will($this->returnValue($data["streetline"]));
        $addressMock->expects($this->any())
            ->method('getCity')
            ->will($this->returnValue($data["city"]));
        $addressMock->expects($this->any())
            ->method('getPostcode')
            ->will($this->returnValue($data["postcode"]));
        $addressMock->expects($this->any())
            ->method('getCountryId')
            ->will($this->returnValue($data["country"]));
        $addressMock->expects($this->any())
            ->method('getRegionCode')
            ->will($this->returnValue($data["state"]));

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('isVirtual')
            ->will($this->returnValue(true));
        $quoteMock->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($addressMock));

        $result = $data["result"];

        $this->assertEquals($result,
            $this->requestHelper->populateAddressInformation($quoteMock)
        );
    }

    public function populateAddressInformationDataProvider()
    {
        return [
            'test with state' => [
                [
                    'lastname' => 'Long last name 1234567891011121314151617181920',
                    'firstname' => 'Long first name 1234567891011121314151617181920',
                    'streetline' => 'address line',
                    'city' => 'Montevideo',
                    'postcode' => '1234567891011121314151617181920',
                    'country' => 'US',
                    'state' => 'MVD',
                    'result' => [
                        'BillingSurname' => "Long last name 12345",
                        'BillingFirstnames' => "Long first name 1234",
                        'BillingAddress1' => "address line",
                        'BillingCity' => "Montevideo",
                        'BillingPostCode' => "1234567891",
                        'BillingCountry' => "US",
                        'BillingState' => "MV",
                        'DeliverySurname' => "Long last name 12345",
                        'DeliveryFirstnames' => "Long first name 1234",
                        'DeliveryAddress1' => "address line",
                        'DeliveryCity' => "Montevideo",
                        'DeliveryPostCode' => "1234567891",
                        'DeliveryCountry' => "US",
                        'DeliveryState' => "MV",
                    ]
                ]
            ],
            'test without state' => [
                [
                    'lastname' => 'last name short',
                    'firstname' => 'first name short',
                    'streetline' => 'address line',
                    'city' => 'Montevideo',
                    'postcode' => '123456789',
                    'country' => 'UY',
                    'state' => 'MVD',
                    'result' => [
                        'BillingSurname' => "last name short",
                        'BillingFirstnames' => "first name short",
                        'BillingAddress1' => "address line",
                        'BillingCity' => "Montevideo",
                        'BillingPostCode' => "123456789",
                        'BillingCountry' => "UY",
                        'DeliverySurname' => "last name short",
                        'DeliveryFirstnames' => "first name short",
                        'DeliveryAddress1' => "address line",
                        'DeliveryCity' => "Montevideo",
                        'DeliveryPostCode' => "123456789",
                        'DeliveryCountry' => "UY"
                    ]
                ]
            ]
        ];
    }
}
