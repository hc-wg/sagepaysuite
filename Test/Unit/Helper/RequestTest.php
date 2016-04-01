<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Helper;

use Ebizmarts\SagePaySuite\Model\Config;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    protected $requestHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_configMock;

    protected function setUp()
    {
        $this->_configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->requestHelper = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Request',
            [
                'config' => $this->_configMock
            ]
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
                        'CustomerEMail' => null,
                        'BillingAddress2' => 'address line',
                        'BillingPhone' => false,
                        'DeliveryAddress2' => 'address line',
                        'DeliveryPhone' => false
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
                        'DeliveryCountry' => "UY",
                        'CustomerEMail' => null,
                        'BillingAddress2' => 'address line',
                        'BillingPhone' => false,
                        'DeliveryAddress2' => 'address line',
                        'DeliveryPhone' => false
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider populatePaymentAmountDataProvider
     */
    public function testPopulatePaymentAmount($data)
    {
        $this->_configMock->expects($this->once())
            ->method('getCurrencyCode')
            ->will($this->returnValue($data['currency']));
        $this->_configMock->expects($this->once())
            ->method('getCurrencyConfig')
            ->will($this->returnValue($data['currency_setting']));

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->setMethods(["getBaseGrandTotal", "getGrandTotal"])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getBaseGrandTotal')
            ->willReturn("100");
        $quoteMock->expects($this->any())
            ->method('getGrandTotal')
            ->will($this->returnValue(200));

        $result = $data["result"];

        $this->assertEquals(
            $result,
            $this->requestHelper->populatePaymentAmount($quoteMock, $data['isRestRequest'])
        );
    }

    public function populatePaymentAmountDataProvider()
    {
        return [
            'test with PI base' => [
                [
                    'currency_setting' => Config::CURRENCY_BASE,
                    'isRestRequest' => true,
                    'currency' => 'USD',
                    'result' => [
                        'amount' => 10000,
                        'currency' => 'USD'
                    ]
                ]
            ],
            'test with PI switcher' => [
                [
                    'currency_setting' => Config::CURRENCY_SWITCHER,
                    'isRestRequest' => true,
                    'currency' => 'EUR',
                    'result' => [
                        'amount' => 20000,
                        'currency' => 'EUR'
                    ]
                ]
            ],
            'test without PI base' => [
                [
                    'currency_setting' => Config::CURRENCY_BASE,
                    'isRestRequest' => false,
                    'currency' => 'USD',
                    'result' => [
                        'Amount' => 100.00,
                        'Currency' => 'USD'
                    ]
                ]
            ]
        ];
    }

    public function testGetOrderDescription()
    {
        $this->assertEquals(
            __("Online MOTO transaction."),
            $this->requestHelper->getOrderDescription(true)
        );
    }

    public function populateBasketInformationDataProvider()
    {
        return [
            'test Sage50' =>
                [
                    [
                        'lines' => 2,
                        'sku' => 'WSH08-28-Purple',
                        'name' => 'SybilRunningShort',
                        'qty' => 1,
                        'priceInclTax' => 44,
                        'taxAmount' => 0,
                        'shippingDescription' => 'BestWay-TableRate',
                        'shippingAmount' => 15,
                        'shippingTaxAmount' => 0,
                        'parentItem' => false,
                        'format' => \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_Sage50,
                        'id' => null,
                        'firstName' => null,
                        'lastName' => null,
                        'middleName' => null,
                        'prefix' => null,
                        'email' => null,
                        'telephone' => null,
                        'streetLine' => null,
                        'city' => null,
                        'regionCode' => null,
                        'country' => null,
                        'postCode' => null,
                        'fax' => null,
                        'isMultishipping' => null,
                        'allAddresses' => null,
                        'method' => null,
                    ]
                ]
            ,
            'test XML' =>
                [
                    [
                        'name' => 'SybilRunningShort',
                        'sku' => 'WSH08-28-Pur',
                        'id' => null,
                        'qty' => 1,
                        'taxAmount' => 0,
                        'total' => 16,
                        'firstName' => 'first name',
                        'lastName' => 'last name',
                        'middleName' => 'm',
                        'prefix' => 'pref',
                        'email' => 'email',
                        'telephone' => '123456',
                        'streetLine' => 'streetLine',
                        'city' => 'city',
                        'country' => 'co',
                        'postCode' => '11222',
                        'shippingAmount' => 15,
                        'shippingTaxAmount' => 1,
                        'priceInclTax' => 16,
                        'fax' => '11222',
                        'parentItem' => false,
                        'format' => \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_XML,
                        'shippingDescription' => 'desc',
                        'regionCode' => 'rc',
                        'allAddresses' => array(),
                        'isMultishipping' => false,
                        'method' => 'sagepayserver',
                    ]
                ]
        ];
    }

    /**
     * @dataProvider populateBasketInformationDataProvider
     * @param $data
     */
    public function testPopulateBasketInformation($data)
    {
        $basket = null;

        if ($data['format'] == \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_Sage50)
        {
            //TODO: esto se puede mejorar para que no sea tan fijo a este caso
            $basket = array(
                'Basket' =>
                    $data['lines'] . ':' . '[' .
                    $data['sku'] . '] ' .
                    $data['name'] . ':' .
                    $data['qty'] . ':' .
                    $data['priceInclTax'] . ':' .
                    number_format($data['taxAmount'], 3) . ':' .
                    $data['priceInclTax'] * $data['qty'] . ':' .
                    $data['priceInclTax'] * $data['qty'] . ':' .
                    $data['shippingDescription'] . ':' .
                    '1' . ':' .
                    $data['shippingAmount'] . ':' .
                    $data['shippingTaxAmount'] . ':' .
                    ($data['shippingAmount'] + $data['shippingTaxAmount']) . ':' .
                    ($data['shippingAmount'] + $data['shippingTaxAmount'])
            );
        }
        elseif ($data['format'] == \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_XML)
        {
            //TODO: <productCode/>????
            $basket = array(
                'BasketXML' =>
                    '<?xml version="1.0" encoding="utf-8"?>' .
                    '<basket>' .
                    '<item>' .
                            '<description>' . $data['name'] . '</description>' .
                            '<productSku>' . $data['sku'] . '</productSku>' .
                            '<productCode/>' .
                            '<quantity>' . $data['qty'] . '</quantity>' .
                            '<unitNetAmount>' . number_format($data['priceInclTax'], 2) . '</unitNetAmount>' .
                            '<unitTaxAmount>' . number_format($data['taxAmount'], 2) . '</unitTaxAmount>' .
                            '<unitGrossAmount>' . number_format($data['total'], 2) . '</unitGrossAmount>' .
                            '<totalGrossAmount>' . number_format($data['total'], 2) . '</totalGrossAmount>' .
                            '<recipientFName>' . $data['firstName'] . '</recipientFName>' .
                            '<recipientLName>' . $data['lastName'] . '</recipientLName>' .
                            '<recipientMName>' . $data['middleName'] . '</recipientMName>' .
                            '<recipientSal>' . $data['prefix'] . '</recipientSal>' .
                            '<recipientEmail>' . $data['email'] . '</recipientEmail>' .
                            '<recipientPhone>' . $data['telephone'] . '</recipientPhone>' .
                            '<recipientAdd1>' . $data['streetLine'] . '</recipientAdd1>' .
                            '<recipientAdd2>' . $data['streetLine'] . '</recipientAdd2>' .
                            '<recipientCity>' . $data['city'] . '</recipientCity>' .
                            '<recipientCountry>' . $data['country'] . '</recipientCountry>' .
                            '<recipientPostCode>' . $data['postCode'] . '</recipientPostCode>' .
                        '</item>' .
                        '<deliveryNetAmount>' . number_format($data['shippingAmount'], 2) . '</deliveryNetAmount>' .
                        '<deliveryTaxAmount>' . number_format($data['shippingTaxAmount'], 2) . '</deliveryTaxAmount>' .
                        '<deliveryGrossAmount>' . number_format($data['priceInclTax'], 2) . '</deliveryGrossAmount>' .
                        '<shippingFaxNo>' . $data['fax'] . '</shippingFaxNo>' .
                    '</basket>'
            );
        }

        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'getShippingDescription',
                'getShippingAmount',
                'getShippingTaxAmount',
                'getFirstname',
                'getLastname',
                'getMiddlename',
                'getPrefix',
                'getEmail',
                'getTelephone',
                'getStreetLine',
                'getCity',
                'getRegionCode',
                'getCountry',
                'getPostcode',
                'getFax'
            ))->getMock();
          $addressMock->expects($this->any())
             ->method('getShippingDescription')
             ->willReturn($data['shippingDescription']);
         $addressMock->expects($this->any())
             ->method('getShippingAmount')
             ->willReturn($data['shippingAmount']);
         $addressMock->expects($this->any())
             ->method('getShippingTaxAmount')
             ->willReturn($data['shippingTaxAmount']);
        //no?
         $addressMock->expects($this->any())
             ->method('getFirstname')
             ->willReturn($data['firstName']);
         $addressMock->expects($this->any())
             ->method('getLastname')
             ->willReturn($data['lastName']);
         $addressMock->expects($this->any())
             ->method('getMiddlename')
             ->willReturn($data['middleName']);
         $addressMock->expects($this->any())
             ->method('getPrefix')
             ->willReturn($data['prefix']);
        $addressMock->expects($this->any())
            ->method('getEmail')
            ->willReturn($data['email']);
        $addressMock->expects($this->any())
            ->method('getTelephone')
            ->willReturn($data['telephone']);
        $addressMock->expects($this->any())
            ->method('getStreetLine')
            ->willReturn($data['streetLine']);
//TODO: Separate line 1 and line 2
//        $addressMock->expects($this->any())
//            ->method('getStreetLine')
//            ->with(2)
//            ->willReturn($data['streetLine1']);
       $addressMock->expects($this->any())
            ->method('getCity')
            ->willReturn($data['city']);
      $addressMock->expects($this->any())
            ->method('getRegionCode')
            ->willReturn($data['regionCode']);
      $addressMock->expects($this->any())
            ->method('getCountry')
            ->willReturn($data['country']);
      $addressMock->expects($this->any())
            ->method('getPostcode')
            ->willReturn($data['postCode']);
      $addressMock->expects($this->any())
            ->method('getFax')
            ->willReturn($data['fax']);

        $itemMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Item')
            ->setMethods(array(
                'getParentItem',
                'getQty',
                'getTaxAmount',
                'getPriceInclTax',
                'getSku',
                'getName',
                'getId'
            ))
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->expects($this->any())
            ->method('getParentItem')
            ->willReturn($data['parentItem']);
        $itemMock->expects($this->any())
            ->method('getQty')
            ->willReturn($data['qty']);
        $itemMock->expects($this->any())
            ->method('getTaxAmount')
            ->willReturn($data['taxAmount']);
        $itemMock->expects($this->any())
            ->method('getPriceInclTax')
            ->willReturn($data['priceInclTax']);
        $itemMock->expects($this->any())
            ->method('getSku')
            ->willReturn($data['sku']);
        $itemMock->expects($this->any())
            ->method('getName')
            ->willReturn($data['name']);
        $itemMock->expects($this->any())
            ->method('getId')
            ->willReturn($data['id']);

        $items[] = $itemMock;

        $itemsCollectionMock = $this
            ->getMockBuilder('Magento\Quote\Model\ResourceModel\Quote\Item\Collection')
            ->disableOriginalConstructor()
            ->getMock();
        $itemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items));

        $payment = $this
            ->getMockBuilder('\Magento\Quote\Model\Quote\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $payment->expects($this->any())
            ->method('getMethod')
            ->willReturn($data['method']);

        $quoteMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('getItemsCollection')
            ->willReturn($itemsCollectionMock);
        $quoteMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($addressMock);
        $quoteMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($addressMock);
        $quoteMock->expects($this->any())
            ->method('getIsMultiShipping')
            ->willReturn($data['isMultishipping']);
        $quoteMock->expects($this->any())
            ->method('getPayment')
            ->willReturn($payment);
        $quoteMock->expects($this->any())
            ->method('getAllAddresses')
            ->willReturn($data['allAddresses']);

         $this->_configMock->expects($this->any())
             ->method('getBasketFormat')
             ->willReturn($data['format']);

        $this->assertEquals(
            $basket, $this->requestHelper->populateBasketInformation($quoteMock)
         );

     }

	public function testGetReferrerId()
    {
        $this->assertEquals(
            __("01bf51f9-0dcd-49dd-a07a-3b1f918c77d7"),
            $this->requestHelper->getReferrerId()
        );
    }
}

