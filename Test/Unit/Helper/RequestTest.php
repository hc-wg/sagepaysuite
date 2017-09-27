<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Helper;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Symfony\Component\DependencyInjection\SimpleXMLElement;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    private $objectManagerHelper;
    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    private $requestHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $configMock;

    private $objectManagerMock;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManager\ObjectManager::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);
        $this->requestHelper = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Request',
            ['config' => $this->configMock, 'objectManager' => $this->objectManagerMock]
        );
    }
    // @codingStandardsIgnoreEnd

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

        $this->assertEquals(
            $result,
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

    private function makeQuoteMockWithMethods($methodsToMock = [])
    {
        return $this
        ->getMockBuilder('Magento\Quote\Model\Quote')
        ->setMethods($methodsToMock)
        ->disableOriginalConstructor()
        ->getMock();
    }

    /**
     * @dataProvider populatePaymentAmountDataProvider
     */
    public function testPopulatePaymentAmount($data)
    {
        $storeMock = $this->makeStoreMock($data);

        $configMock = $this->makeConfigMock($storeMock);
        //$configMock->expects($this->once())->method('getQuoteCurrencyCode')->willReturn($data['currency']);
        $configMock->expects($this->exactly(2))->method('getCurrencyConfig')->willReturn($data['currency_setting']);
        $configMock->expects($this->exactly(3))->method('setConfigurationScopeId')->with(1234);

        $this->requestHelper = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Request',
            [
                'config'        => $configMock,
                'objectManager' => $this->objectManagerMock
            ]
        );

        $quoteMock = $this->makeQuoteMockWithMethods(["getStoreId", "getBaseGrandTotal", "getGrandTotal", "getQuoteCurrencyCode"]);
        $quoteMock->expects($this->exactly(3))->method('getStoreId')->willReturn(1234);
        $quoteMock->expects($this->any())->method('getBaseGrandTotal')->willReturn(100);
        $quoteMock->expects($this->any())->method('getGrandTotal')->willReturn(200);


        $result = $data["result"];
        $actual = $this->requestHelper->populatePaymentAmountAndCurrency($quoteMock, $data['isRestRequest']);
        $this->assertEquals($result, $actual);
    }

    public function populatePaymentAmountDataProvider()
    {
        return [
            'test with PI base' => [
                [
                    'currency_setting' => Config::CURRENCY_BASE,
                    'isRestRequest' => true,
                    'result' => [
                        'amount' => 10000,
                        'currency' => 'GBP'
                    ]
                ]
            ],
            'test PI admin diff order' => [
                [
                    'currency_setting' => Config::CURRENCY_BASE,
                    'isRestRequest' => true,
                    'result' => [
                        'amount' => 10000,
                        'currency' => 'GBP'
                    ]
                ]
            ],
            'test with PI switcher' => [
                [
                    'currency_setting' => Config::CURRENCY_SWITCHER,
                    'isRestRequest' => true,
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
                    'result' => [
                        'Amount' => 100.00,
                        'Currency' => 'GBP'
                    ]
                ]
            ],
            'test issue 166' => [
                [
                    'currency_setting' => Config::CURRENCY_BASE,
                    'isRestRequest' => false,
                    'result' => [
                        'Amount' => '100.00',
                        'Currency' => 'GBP'
                    ]
                ]
            ]
        ];
    }

    private function makeConfigMock($storeMock)
    {
        $scopeConfigMock = $this->getMockBuilder("Magento\Framework\App\Config\ScopeConfigInterface")
            ->disableOriginalConstructor()
            ->getMock();
        $storeManagerMock = $this->getMockBuilder("Magento\Store\Model\StoreManagerInterface")
            ->disableOriginalConstructor()
            ->getMock();
        $storeManagerMock->expects($this->once())->method("getStore")->with(1234)->willReturn($storeMock);

        $suiteLoggerMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Model\Logger\Logger")
            ->disableOriginalConstructor()
            ->getMock();

        $configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->setMethods(["getCurrencyConfig", "setConfigurationScopeId"])
            ->setConstructorArgs([
                "_scopeConfig" => $scopeConfigMock,
                "storemManager" => $storeManagerMock,
                "suiteLogger" => $suiteLoggerMock
            ])
            ->getMock();

        return $configMock;
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
                        'product_id' => 1234,
                        'price' => 44,
                        'taxAmount' => 0,
                        'shippingDescription' => 'BestWay-TableRate',
                        'shippingAmount' => 15,
                        'shippingTaxAmount' => 0,
                        'deliveryGrossAmount' => 15,
                        'parentItem' => false,
                        'format' => \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_SAGE50,
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
                        'product_id' => 56,
                        'id' => null,
                        'qty' => 1,
                        'taxAmount' => 0,
                        'unitTaxAmount' => 0,
                        'unitGrossAmount' => 16,
                        'totalGrossAmount' => 16,
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
                        'deliveryGrossAmount' => 16,
                        'priceInclTax' => 16,
                        'price' => 16,
                        'fax' => '11222',
                        'parentItem' => false,
                        'format' => \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_XML,
                        'shippingDescription' => 'desc',
                        'regionCode' => 'rc',
                        'allAddresses' => [],
                        'isMultishipping' => false,
                        'method' => 'sagepayserver',
                    ]
                ]
            ,
            'test XML special chars' =>
                [
                    [
                        'name' => 'Pursuit Lumaflex&trade; Tone Band',
                        'expected_name' => 'Pursuit Lumaflex Tone Band',
                        'sku' => '24-UG02',
                        'product_id' => 56,
                        'id' => null,
                        'qty' => 1,
                        'taxAmount' => 0,
                        'unitTaxAmount' => 0,
                        'unitGrossAmount' => 16,
                        'totalGrossAmount' => 16,
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
                        'deliveryGrossAmount' => 16,
                        'priceInclTax' => 16,
                        'price' => 16,
                        'fax' => '11222',
                        'parentItem' => false,
                        'format' => \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_XML,
                        'shippingDescription' => 'desc',
                        'regionCode' => 'rc',
                        'allAddresses' => [],
                        'isMultishipping' => false,
                        'method' => 'sagepayserver',
                    ]
                ],
            'test XML with tax' =>
                [
                    [
                        'name' => 'SybilRunningShort',
                        'product_id' => 66,
                        'sku' => 'taxable-WSH0',
                        'id' => null,
                        'qty' => 3,
                        'taxAmount' => 120,
                        'unitTaxAmount' => 40,
                        'unitGrossAmount' => 240,
                        'totalGrossAmount' => 720,
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
                        'deliveryGrossAmount' => 16,
                        'price' => 200,
                        'priceInclTax' => 240,
                        'fax' => '11222',
                        'parentItem' => false,
                        'format' => \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_XML,
                        'shippingDescription' => 'desc',
                        'regionCode' => 'rc',
                        'allAddresses' => [],
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

        if ($data['format'] == \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_SAGE50) {
            $basket = [
                'Basket' =>
                    $data['lines'] . ':' . '[' .
                    $data['sku'] . '] ' .
                    $data['name'] . ':' .
                    $data['qty'] . ':' .
                    number_format($data['priceInclTax'], 2) . ':' .
                    number_format($data['taxAmount'], 3) . ':' .
                    number_format($data['priceInclTax'] * $data['qty'], 2) . ':' .
                    number_format($data['priceInclTax'] * $data['qty'], 2) . ':' .
                    $data['shippingDescription'] . ':' .
                    '1' . ':' .
                    $data['shippingAmount'] . ':' .
                    $data['shippingTaxAmount'] . ':' .
                    ($data['shippingAmount'] + $data['shippingTaxAmount']) . ':' .
                    ($data['shippingAmount'] + $data['shippingTaxAmount'])
            ];
        } elseif ($data['format'] == \Ebizmarts\SagePaySuite\Model\Config::BASKETFORMAT_XML) {
            $xmlDesc = "<description>{$data['name']}</description>";
            if (array_key_exists('expected_name', $data)) {
                $xmlDesc = "<description>{$data['expected_name']}</description>";
            }

            $basket = [
                'BasketXML' =>
            '<?xml version="1.0" encoding="utf-8"?>' .
            '<basket>' .
            '<item>' .
                    $xmlDesc .
                    '<productSku>' . $data['sku'] . '</productSku>' .
                    '<productCode>' . $data['product_id'] . '</productCode>' .
                    '<quantity>' . $data['qty'] . '</quantity>' .
                    '<unitNetAmount>' . number_format($data['price'], 2) . '</unitNetAmount>' .
                    '<unitTaxAmount>' . number_format($data['unitTaxAmount'], 2) . '</unitTaxAmount>' .
                    '<unitGrossAmount>' . number_format($data['unitGrossAmount'], 2) . '</unitGrossAmount>' .
                    '<totalGrossAmount>' . number_format($data['totalGrossAmount'], 2) . '</totalGrossAmount>' .
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
                '<deliveryGrossAmount>' . number_format($data['deliveryGrossAmount'], 2) . '</deliveryGrossAmount>' .
                '<shippingFaxNo>' . $data['fax'] . '</shippingFaxNo>' .
            '</basket>'
            ];
        }

        $addressMock = $this
            ->getMockBuilder('Magento\Quote\Model\Quote\Address')
            ->disableOriginalConstructor()
            ->setMethods([
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
            ])->getMock();
          $addressMock->expects($this->any())
             ->method('getShippingDescription')
             ->willReturn($data['shippingDescription']);
         $addressMock->expects($this->any())
             ->method('getShippingAmount')
             ->willReturn($data['shippingAmount']);
         $addressMock->expects($this->any())
             ->method('getShippingTaxAmount')
             ->willReturn($data['shippingTaxAmount']);
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
            ->setMethods([
                'getParentItem',
                'getProductId',
                'getQty',
                'getTaxAmount',
                'getPriceInclTax',
                'getPrice',
                'getSku',
                'getName',
                'getId',
                'toArray'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->expects($this->any())
            ->method('toArray')
            ->willReturn([]);
        $itemMock->expects($this->any())
            ->method('getParentItem')
            ->willReturn($data['parentItem']);
        $itemMock->expects($this->any())
            ->method('getQty')
            ->willReturn($data['qty']);
        $itemMock->expects($this->any())
            ->method('getProductId')
            ->willReturn($data['product_id']);
        $itemMock->expects($this->any())
            ->method('getTaxAmount')
            ->willReturn($data['taxAmount']);
        $itemMock->expects($this->any())
            ->method('getPriceInclTax')
            ->willReturn($data['priceInclTax']);
        $itemMock->expects($this->any())
            ->method('getPrice')
            ->willReturn($data['price']);
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

         $this->configMock->expects($this->any())
             ->method('getBasketFormat')
             ->willReturn($data['format']);

        $simpleInstance = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><basket />');
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);

        $this->requestHelper = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Request',
            ['config' => $this->configMock, 'objectManager' => $this->objectManagerMock]
        );

        $this->assertEquals(
            current($basket),
            current($this->requestHelper->populateBasketInformation($quoteMock))
        );
    }

    public function testGetReferrerId()
    {
        $this->assertEquals(
            "01bf51f9-0dcd-49dd-a07a-3b1f918c77d7",
            $this->requestHelper->getReferrerId()
        );
    }

    /**
     * @dataProvider basketXMLProvider
     * @param float $totalBasketAmount
     * @param string $basket
     */
    public function testValidateBasketXmlAmounts($totalBasketAmount, $basket)
    {
        $totalBasketAmount = null;

        $simpleInstance = new \SimpleXMLElement($basket);
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);
        $this->requestHelper = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Request',
            ['config' => $this->configMock, 'objectManager' => $this->objectManagerMock]
        );

        $this->assertTrue($this->requestHelper->validateBasketXmlAmounts($basket));
    }

    /**
     * @param $totalBasketAmount
     * @param $basket
     * @dataProvider basketXMLProvider
     */
    public function testGetBasketXmlTotals($totalBasketAmount, $basket)
    {
        $simpleInstance = new \SimpleXMLElement($basket);
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);
        $this->requestHelper = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Request',
            ['config' => $this->configMock, 'objectManager' => $this->objectManagerMock]
        );

        $this->assertEquals($totalBasketAmount, $this->requestHelper->getBasketXmlTotalAmount($basket));
    }

    public function basketXMLProvider()
    {
        return [
            'Example from customer.' => [36.17,
              '<basket>
                    <item>
                        <description>Acqua di Parma Blu Mediterraneo Cedro di Taormina Shower Gel 200ml</description>
                        <productSku>AdP-57116</productSku>
                        <productCode>2121</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>31.00</unitNetAmount>
                        <unitTaxAmount>5.17</unitTaxAmount>
                        <unitGrossAmount>36.17</unitGrossAmount>
                        <totalGrossAmount>36.17</totalGrossAmount>
                        <recipientFName>Tester</recipientFName>
                        <recipientLName>Testerec</recipientLName>
                        <recipientEmail>tester@example.com</recipientEmail>
                        <recipientPhone>111111</recipientPhone>
                        <recipientAdd1>61 Wellfield Road</recipientAdd1>
                        <recipientCity>Cardiff</recipientCity>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>CF24 3DG</recipientPostCode>
                    </item>
                    <deliveryNetAmount>0.00</deliveryNetAmount>
                    <deliveryTaxAmount>0.00</deliveryTaxAmount>
                    <deliveryGrossAmount>0.00</deliveryGrossAmount>
                </basket>',
            ],
            'products and no TRIPs data' => [94.00,
                '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>DVD 1</description> 
                        <productSku>TIMESKU</productSku> 
                        <productCode>1234567</productCode>
                        <quantity>2</quantity> 
                        <unitNetAmount>24.50</unitNetAmount> 
                        <unitTaxAmount>00.50</unitTaxAmount> 
                        <unitGrossAmount>25.00</unitGrossAmount>
                        <totalGrossAmount>50.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName> 
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName> 
                        <recipientSal>MR</recipientSal> 
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone> 
                        <recipientAdd1>add1</recipientAdd1> 
                        <recipientAdd2>add2</recipientAdd2> 
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState> 
                        <recipientCountry>GB</recipientCountry> 
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg> 
                    </item>  
                    <item> 
                        <description>DVD 2</description> 
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode> 
                        <quantity>1</quantity> 
                        <unitNetAmount>24.99</unitNetAmount>
                        <unitTaxAmount>00.99</unitTaxAmount>
                        <unitGrossAmount>25.98</unitGrossAmount>
                        <totalGrossAmount>25.98</totalGrossAmount> 
                        <recipientFName>firstname</recipientFName> 
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName> 
                        <recipientSal>MR</recipientSal> 
                        <recipientEmail> firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone> 
                        <recipientAdd1>add1</recipientAdd1> 
                        <recipientAdd2>add2</recipientAdd2> 
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry> 
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Congrats</itemGiftMsg> 
                    </item> 
                    <deliveryNetAmount>4.02</deliveryNetAmount>
                    <deliveryTaxAmount>20.00</deliveryTaxAmount> 
                    <deliveryGrossAmount>24.02</deliveryGrossAmount> 
                    <discounts> 
                    <discount>
                        <fixed>5</fixed>
                        <description>Save 5 pounds</description>
                    </discount> 
                    <discount>
                        <fixed>1</fixed>
                        <description>Spend 5 pounds and save 1 pound</description>
                    </discount> 
                    </discounts>
                    <shipId>SHIP00002</shipId> 
                    <shippingMethod>N</shippingMethod> 
                    <shippingFaxNo>1234567890</shippingFaxNo>
                 </basket>'
            ],
            'Tour Operators' => [94,
                '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <deliveryNetAmount>5.00</deliveryNetAmount>
                    <deliveryTaxAmount>0.00</deliveryTaxAmount>
                    <deliveryGrossAmount>5.00</deliveryGrossAmount>
                    <discounts> 
                    <discount>
                        <fixed>5</fixed>
                        <description>Save 5 pounds</description>
                    </discount> 
                    <discount>
                        <fixed>1</fixed>
                        <description>Spend 5 pounds and save 1 pound</description>
                    </discount> 
                    </discounts>
                    <shipId>SHIP00002</shipId>
                    <shippingMethod>N</shippingMethod>
                    <shippingFaxNo>1234567890</shippingFaxNo>
                    <tourOperator>
                        <checkIn>2012-10-12</checkIn>
                        <checkOut>2012-10-29</checkOut>
                    </tourOperator>
                </basket>'
            ],
            'Car rental' => [80,
               '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <deliveryNetAmount>5.00</deliveryNetAmount>
                    <deliveryTaxAmount>0.00</deliveryTaxAmount>
                    <deliveryGrossAmount>5.00</deliveryGrossAmount>
                    <discounts> 
                    <discount>
                        <fixed>5</fixed>
                        <description>Save 5 pounds</description>
                    </discount> 
                    <discount>
                        <fixed>1</fixed>
                        <description>Spend 5 pounds and save 1 pound</description>
                    </discount>
                    <discount>
                        <fixed>14</fixed>
                        <description>Spend 5 pounds and save 14 pound</description>
                    </discount> 
                    </discounts>
                    <shipId>SHIP00002</shipId>
                    <shippingMethod>N</shippingMethod>
                    <shippingFaxNo>1234567890</shippingFaxNo>
                    <carRental>
                        <checkIn>2012-10-12</checkIn>
                        <checkOut>2012-10-29</checkOut>
                    </carRental>
                </basket>'
            ],
            'Hotel reservation' => [95,
                '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <deliveryNetAmount>5.00</deliveryNetAmount>
                    <deliveryTaxAmount>0.00</deliveryTaxAmount>
                    <deliveryGrossAmount>5.00</deliveryGrossAmount>
                    <discounts> 
                    <discount>
                        <fixed>5</fixed>
                        <description>Save 5 pounds</description>
                    </discount>
                    </discounts>
                    <shipId>SHIP00002</shipId>
                    <shippingMethod>N</shippingMethod>
                    <shippingFaxNo>1234567890</shippingFaxNo>
                    <hotel>
                        <checkIn>2012-10-12</checkIn>
                        <checkOut>2012-10-13</checkOut>
                        <numberInParty>1</numberInParty>
                        <guestName>Mr Smith</guestName>
                        <folioRefNumber>A1000</folioRefNumber>
                        <confirmedReservation>Y</confirmedReservation>
                        <dailyRoomRate>150.00</dailyRoomRate>
                    </hotel>
                </basket>'
            ],
            'Cruise' => [
                115,
                '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <deliveryNetAmount>20.00</deliveryNetAmount>
                    <deliveryTaxAmount>5.00</deliveryTaxAmount>
                    <deliveryGrossAmount>20.00</deliveryGrossAmount>
                    <shipId>SHIP00002</shipId>
                    <shippingMethod>N</shippingMethod>
                    <shippingFaxNo>1234567890</shippingFaxNo>
                    <cruise>
                        <checkIn>2012-10-12</checkIn>
                        <checkOut>2012-10-29</checkOut>
                    </cruise>
                </basket>'
            ],
            'Airline' => [
                290,
                '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour2</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>2</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>190.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <deliveryNetAmount>5.00</deliveryNetAmount>
                    <deliveryTaxAmount>0.00</deliveryTaxAmount>
                    <deliveryGrossAmount>5.00</deliveryGrossAmount>
                    <shipId>SHIP00002</shipId>
                    <shippingMethod>N</shippingMethod>
                    <shippingFaxNo>1234567890</shippingFaxNo>
                    <airline>
                        <ticketNumber>12345678901</ticketNumber>
                        <airlineCode>123</airlineCode>
                        <agentCode>12345678</agentCode>
                        <agentName>26characterslong</agentName>
                        <restrictedTicket>0</restrictedTicket>
                        <passengerName>29characterslong</passengerName>
                        <originatingAirport>BLR</originatingAirport>
                            <segment>
                                <carrierCode>ABC</carrierCode>
                                <class>A01</class>
                                <stopOver>1</stopOver>
                                <legDepartureDate>2012-03-20</legDepartureDate>
                                <destination>LHR</destination>
                                <fareBasis>FARE12</fareBasis>
                            </segment>
                        <customerCode>20characterslong</customerCode>
                        <flightNumber>BA0118</flightNumber>
                        <invoiceNumber>123123123123123</invoiceNumber>
                    </airline>
                </basket>'
            ],
            'Diners' => [
                100,
                '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <deliveryNetAmount>5.00</deliveryNetAmount>
                    <deliveryTaxAmount>0.00</deliveryTaxAmount>
                    <deliveryGrossAmount>5.00</deliveryGrossAmount>
                    <shipId>SHIP00002</shipId>
                    <shippingMethod>N</shippingMethod>
                    <shippingFaxNo>1234567890</shippingFaxNo>
                    <dinerCustomerRef>123123123</dinerCustomerRef>       
                </basket>'
            ]
        ];
    }

    public function testUnsetBasketXMLIfAmountsDontMatchAmountsDontMatch()
    {
        $requestData               = [];
        $requestData['Vendorname'] = 'alfa';
        $requestData['Amount']     = 60.78;
        $requestData['BasketXML']  = '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <deliveryNetAmount>5.00</deliveryNetAmount>
                    <deliveryTaxAmount>0.00</deliveryTaxAmount>
                    <deliveryGrossAmount>5.00</deliveryGrossAmount>
                    <discounts> 
                    <discount>
                        <fixed>5</fixed>
                        <description>Save 5 pounds</description>
                    </discount>
                    </discounts>
                    <shipId>SHIP00002</shipId>
                    <shippingMethod>N</shippingMethod>
                    <shippingFaxNo>1234567890</shippingFaxNo>
                    <hotel>
                        <checkIn>2012-10-12</checkIn>
                        <checkOut>2012-10-13</checkOut>
                        <numberInParty>1</numberInParty>
                        <guestName>Mr Smith</guestName>
                        <folioRefNumber>A1000</folioRefNumber>
                        <confirmedReservation>Y</confirmedReservation>
                        <dailyRoomRate>150.00</dailyRoomRate>
                    </hotel>
                </basket>';

        $this->assertArrayHasKey('BasketXML', $requestData);

        $simpleInstance = new \SimpleXMLElement($requestData['BasketXML']);
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);
        $this->requestHelper = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Request',
            ['config' => $this->configMock, 'objectManager' => $this->objectManagerMock]
        );

        $requestData = $this->requestHelper->unsetBasketXMLIfAmountsDontMatch($requestData);

        $this->assertArrayNotHasKey('BasketXML', $requestData);
        $this->assertArrayHasKey('Amount', $requestData);
    }

    public function testUnsetBasketXMLIfAmountsDontMatchAmountsMatch()
    {
        $requestData               = [];
        $requestData['Vendorname'] = 'alfa';
        $requestData['Amount']     = 95;
        $requestData['BasketXML']  = '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <deliveryNetAmount>5.00</deliveryNetAmount>
                    <deliveryTaxAmount>0.00</deliveryTaxAmount>
                    <deliveryGrossAmount>5.00</deliveryGrossAmount>
                    <discounts> 
                    <discount>
                        <fixed>5</fixed>
                        <description>Save 5 pounds</description>
                    </discount>
                    </discounts>
                    <shipId>SHIP00002</shipId>
                    <shippingMethod>N</shippingMethod>
                    <shippingFaxNo>1234567890</shippingFaxNo>
                    <hotel>
                        <checkIn>2012-10-12</checkIn>
                        <checkOut>2012-10-13</checkOut>
                        <numberInParty>1</numberInParty>
                        <guestName>Mr Smith</guestName>
                        <folioRefNumber>A1000</folioRefNumber>
                        <confirmedReservation>Y</confirmedReservation>
                        <dailyRoomRate>150.00</dailyRoomRate>
                    </hotel>
                </basket>';

        $this->assertArrayHasKey('BasketXML', $requestData);
        $this->assertArrayHasKey('Amount', $requestData);

        $simpleInstance = new \SimpleXMLElement($requestData['BasketXML']);
        $this->objectManagerMock
            ->method('create')
            ->willReturn($simpleInstance);
        $this->requestHelper = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Helper\Request',
            ['config' => $this->configMock, 'objectManager' => $this->objectManagerMock]
        );

        $requestData = $this->requestHelper->unsetBasketXMLIfAmountsDontMatch($requestData);

        $this->assertArrayHasKey('BasketXML', $requestData);
        $this->assertArrayHasKey('Amount', $requestData);
    }

    public function testValidateBasketXmlLengthTooLong()
    {
        $basket = '<basket>
                    <agentId>johnsmith</agentId>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <item>
                        <description>Tour</description>
                        <productSku>TIMESKU</productSku>
                        <productCode>1234567</productCode>
                        <quantity>1</quantity>
                        <unitNetAmount>90.00</unitNetAmount>
                        <unitTaxAmount>5.00</unitTaxAmount>
                        <unitGrossAmount>95.00</unitGrossAmount>
                        <totalGrossAmount>95.00</totalGrossAmount>
                        <recipientFName>firstname</recipientFName>
                        <recipientLName>lastname</recipientLName>
                        <recipientMName>M</recipientMName>
                        <recipientSal>MR</recipientSal>
                        <recipientEmail>firstname.lastname @test.com</recipientEmail>
                        <recipientPhone>1234567890</recipientPhone>
                        <recipientAdd1>add1</recipientAdd1>
                        <recipientAdd2>add2</recipientAdd2>
                        <recipientCity>city</recipientCity>
                        <recipientState>CA</recipientState>
                        <recipientCountry>GB</recipientCountry>
                        <recipientPostCode>ha412t</recipientPostCode>
                        <itemShipNo>1123</itemShipNo>
                        <itemGiftMsg>Happy Birthday</itemGiftMsg>
                    </item>
                    <deliveryNetAmount>5.00</deliveryNetAmount>
                    <deliveryTaxAmount>0.00</deliveryTaxAmount>
                    <deliveryGrossAmount>5.00</deliveryGrossAmount>
                    <discounts> 
                    <discount>
                        <fixed>5</fixed>
                        <description>Save 5 pounds</description>
                    </discount>
                    </discounts>
                    <shipId>SHIP00002</shipId>
                    <shippingMethod>N</shippingMethod>
                    <shippingFaxNo>1234567890</shippingFaxNo>
                    <hotel>
                        <checkIn>2012-10-12</checkIn>
                        <checkOut>2012-10-13</checkOut>
                        <numberInParty>1</numberInParty>
                        <guestName>Mr Smith</guestName>
                        <folioRefNumber>A1000</folioRefNumber>
                        <confirmedReservation>Y</confirmedReservation>
                        <dailyRoomRate>150.00</dailyRoomRate>
                    </hotel>
                </basket>';

        $this->assertFalse($this->requestHelper->validateBasketXmlLength($basket));
    }

    /**
     * @param $data
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeStoreMock($data)
    {
        $storeMock = $this->getMockBuilder("Magento\Store\Api\Data\StoreInterface")->setMethods([
                "getBaseCurrencyCode",
                "getId",
                "setId",
                "getCode",
                "setCode",
                "getName",
                "setName",
                "getWebsiteId",
                "setWebsiteId",
                "getStoreGroupId",
                "setStoreGroupId",
                "getExtensionAttributes",
                "setExtensionAttributes",
                "getDefaultCurrencyCode",
                "getCurrentCurrencyCode"
            ])->disableOriginalConstructor()->getMock();

        if ($data["currency_setting"] == "base_currency") {
            $storeMock->expects($this->once())->method("getBaseCurrencyCode")->willReturn("GBP");
        }

        if ($data["currency_setting"] == "store_currency") {
            $storeMock->expects($this->once())->method("getDefaultCurrencyCode")->willReturn("USD");
        }

        if ($data["currency_setting"] == "switcher_currency") {
            $storeMock->expects($this->once())->method("getCurrentCurrencyCode")->willReturn("EUR");
        }

        return $storeMock;
    }
}
