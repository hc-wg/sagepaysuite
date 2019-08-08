<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/19/17
 * Time: 11:19 AM
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class PiRequestTest extends \PHPUnit\Framework\TestCase
{

    public function testGetRequesData()
    {
        $billingAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->expects($this->once())->method('getFirstname')->willReturn('Juan');
        $billingAddressMock->expects($this->once())->method('getLastname')->willReturn('Perez');
        $billingAddressMock->expects($this->once())->method('getEmail')->willReturn('juan.perez@example.com');
        $billingAddressMock->expects($this->once())->method('getTelephone')->willReturn('0900 2020');
        $billingAddressMock->expects($this->once())->method('getStreetLine')->with(1)->willReturn('407 St. John Street');
        $billingAddressMock->expects($this->once())->method('getCity')->willReturn('London');
        $billingAddressMock->expects($this->once())->method('getPostcode')->willReturn('EC1V 4AB');
        $billingAddressMock->expects($this->once())->method('getCountryId')->willReturn('GB');

        $deliveryAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $deliveryAddressMock->expects($this->once())->method('getFirstname')->willReturn('Juan');
        $deliveryAddressMock->expects($this->once())->method('getLastname')->willReturn('Perez');
        $deliveryAddressMock->expects($this->once())->method('getStreetLine')->with(1)->willReturn('407 St. John Street');
        $deliveryAddressMock->expects($this->once())->method('getCity')->willReturn('London');
        $deliveryAddressMock->expects($this->once())->method('getPostcode')->willReturn('EC1V 4AB');
        $deliveryAddressMock->expects($this->once())->method('getCountryId')->willReturn('GB');

        $requestHelperMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['populatePaymentAmountAndCurrency', 'getOrderDescription'])
            ->getMock();
        $requestHelperMock
            ->expects($this->once())
            ->method('getOrderDescription')
            ->with(false)
            ->willReturn("Online transaction.");
        $requestHelperMock
            ->expects($this->once())
            ->method('populatePaymentAmountAndCurrency')
            ->willReturn(
                [
                    "amount"   => 1500,
                    "currency" => "GBP"
                ]
            );

        $configMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['get3Dsecure','getSagepayPaymentAction', 'getAvsCvc'])
            ->getMock();
        $configMock->expects($this->once())->method('get3Dsecure')->with(false)->willReturn("UseMSPSetting");
        $configMock->expects($this->once())->method('getSagepayPaymentAction')->willReturn("Payment");
        $configMock->expects($this->once())->method('getAvsCvc')->willReturn("UseMSPSetting");

        $cartMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock->expects($this->once())->method('getBillingAddress')->willReturn($billingAddressMock);
        $cartMock->expects($this->once())->method('getShippingAddress')->willReturn($deliveryAddressMock);
        $cartMock->expects($this->once())->method('getIsVirtual')->willReturn(false);

        /** @var \Ebizmarts\SagePaySuite\Model\PiRequest $piRequestMock */
        $piRequestMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PiRequest::class)
            ->setConstructorArgs([$requestHelperMock, $configMock])
            ->setMethods(['getCart', 'getIsMoto'])
            ->getMock();

        $piRequestMock->setMerchantSessionKey("1EB6A6C0-47CF-4B88-90E2-FC0F31895AD8");
        $piRequestMock->setCardIdentifier("FE646772-6C9F-478B-BF11-9087105FD372");
        $piRequestMock->setVendorTxCode("000000194-2017-01-19-1351141484833874");

        $piRequestMock->expects($this->exactly(4))->method('getCart')->willReturn($cartMock);
        $piRequestMock->expects($this->exactly(3))->method('getIsMoto')->willReturn(false);

        $returnData = [
            'transactionType' => 'Payment',
            'paymentMethod'   => [
                'card'        => [
                    'merchantSessionKey' => "1EB6A6C0-47CF-4B88-90E2-FC0F31895AD8",
                    'cardIdentifier'     => "FE646772-6C9F-478B-BF11-9087105FD372",
                ]
            ],
            'vendorTxCode'      => "000000194-2017-01-19-1351141484833874",
            'description'       => "Online transaction.",
            'customerFirstName' => "Juan",
            'customerLastName'  => "Perez",
            'apply3DSecure'     => "UseMSPSetting",
            'applyAvsCvcCheck'  => "UseMSPSetting",
            'referrerId'        => "01bf51f9-0dcd-49dd-a07a-3b1f918c77d7",
            'customerEmail'     => "juan.perez@example.com",
            'customerPhone'     => "0900 2020",
            'entryMethod'       => "Ecommerce",
            'billingAddress'    => [
                                    'address1'   => "407 St. John Street",
                                    'city'       => "London",
                                    'postalCode' => "EC1V 4AB",
                                    'country'    => "GB",
            ],
            'shippingDetails'  => [
                                    "recipientFirstName" => "Juan",
                                    "recipientLastName"  => "Perez",
                                    "shippingAddress1"   => "407 St. John Street",
                                    "shippingCity"       => "London",
                                    "shippingPostalCode" => "EC1V 4AB",
                                    "shippingCountry"    => "GB",
            ],
            'amount'           => 1500,
            'currency'         => 'GBP'
        ];

        $this->assertEquals($returnData, $piRequestMock->getRequestData());
    }

    public function testGetRequesDataIE()
    {
        $billingAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->expects($this->once())->method('getFirstname')->willReturn('Juan with a lot of names');
        $billingAddressMock->expects($this->once())->method('getLastname')->willReturn('Perez long lastname truncated');
        $billingAddressMock->expects($this->once())->method('getEmail')->willReturn('juan.perez@example.com');
        $billingAddressMock->expects($this->once())->method('getTelephone')->willReturn('0900 2020');
        $billingAddressMock->expects($this->once())->method('getStreetLine')->with(1)->willReturn('50-51 Patrick Street');
        $billingAddressMock->expects($this->once())->method('getCity')->willReturn('Dun Laoghaireinternational long cityrt56123');
        $billingAddressMock->expects($this->once())->method('getCountryId')->willReturn('IE');
        $billingAddressMock->expects($this->once())->method('getPostcode')->willReturn('');

        $deliveryAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $deliveryAddressMock->expects($this->once())->method('getFirstname')->willReturn('Juan lots of names this lad');
        $deliveryAddressMock->expects($this->once())->method('getLastname')->willReturn('Perez Gonzalez more 20 chars');
        $deliveryAddressMock->expects($this->once())->method('getStreetLine')->with(1)->willReturn('50-51 Patrick Street');
        $deliveryAddressMock->expects($this->once())->method('getCity')->willReturn(
            'Dun Laoghaire over 40- chDun Laoghaire over 40 chars'
        );
        $deliveryAddressMock->expects($this->once())->method('getCountryId')->willReturn('IE');
        $deliveryAddressMock->expects($this->once())->method('getPostcode')->willReturn('');

        $requestHelperMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['populatePaymentAmountAndCurrency', 'getOrderDescription'])
            ->getMock();
        $requestHelperMock
            ->expects($this->once())
            ->method('getOrderDescription')
            ->with(false)
            ->willReturn("Online transaction.");
        $requestHelperMock
            ->expects($this->once())
            ->method('populatePaymentAmountAndCurrency')
            ->willReturn(
                [
                    "amount"   => 1500,
                    "currency" => "GBP"
                ]
            );

        $configMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['get3Dsecure','getSagepayPaymentAction', 'getAvsCvc'])
            ->getMock();
        $configMock->expects($this->once())->method('get3Dsecure')->with(false)->willReturn("UseMSPSetting");
        $configMock->expects($this->once())->method('getSagepayPaymentAction')->willReturn("Payment");
        $configMock->expects($this->once())->method('getAvsCvc')->willReturn("UseMSPSetting");

        $cartMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock->expects($this->once())->method('getBillingAddress')->willReturn($billingAddressMock);
        $cartMock->expects($this->once())->method('getShippingAddress')->willReturn($deliveryAddressMock);
        $cartMock->expects($this->once())->method('getIsVirtual')->willReturn(false);

        /** @var \Ebizmarts\SagePaySuite\Model\PiRequest $piRequestMock */
        $piRequestMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PiRequest::class)
            ->setConstructorArgs([$requestHelperMock, $configMock])
            ->setMethods(['getCart', 'getIsMoto'])
            ->getMock();

        $piRequestMock->setMerchantSessionKey("1EB6A6C0-47CF-4B88-90E2-FC0F31895AD8");
        $piRequestMock->setCardIdentifier("FE646772-6C9F-478B-BF11-9087105FD372");
        $piRequestMock->setVendorTxCode("000000194-2017-01-19-1351141484833874");

        $piRequestMock->expects($this->exactly(4))->method('getCart')->willReturn($cartMock);
        $piRequestMock->expects($this->exactly(3))->method('getIsMoto')->willReturn(false);

        $returnData = [
            'transactionType' => 'Payment',
            'paymentMethod'   => [
                'card'        => [
                    'merchantSessionKey' => "1EB6A6C0-47CF-4B88-90E2-FC0F31895AD8",
                    'cardIdentifier'     => "FE646772-6C9F-478B-BF11-9087105FD372",
                ]
            ],
            'vendorTxCode'      => "000000194-2017-01-19-1351141484833874",
            'description'       => "Online transaction.",
            'customerFirstName' => "Juan with a lot of n",
            'customerLastName'  => "Perez long lastname ",
            'apply3DSecure'     => "UseMSPSetting",
            'applyAvsCvcCheck'  => "UseMSPSetting",
            'referrerId'        => "01bf51f9-0dcd-49dd-a07a-3b1f918c77d7",
            'customerEmail'     => "juan.perez@example.com",
            'customerPhone'     => "0900 2020",
            'entryMethod'       => "Ecommerce",
            'billingAddress'    => [
                                    'address1'   => "50-51 Patrick Street",
                                    'city'       => "Dun Laoghaireinternational long cityrt56",
                                    'country'    => "IE",
                                    'postalCode' => "000"
            ],
            'shippingDetails'  => [
                                    "recipientFirstName" => "Juan lots of names t",
                                    "recipientLastName"  => "Perez Gonzalez more ",
                                    "shippingAddress1"   => "50-51 Patrick Street",
                                    "shippingCity"       => "Dun Laoghaire over 40- chDun Laoghaire o",
                                    "shippingCountry"    => "IE",
                                    "shippingPostalCode"   => "000"
            ],
            'amount'           => 1500,
            'currency'         => 'GBP'
        ];

        $this->assertEquals($returnData, $piRequestMock->getRequestData());
    }

    public function testGetRequesDataHK()
    {
        $billingAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->expects($this->once())->method('getFirstname')->willReturn('Juan');
        $billingAddressMock->expects($this->once())->method('getLastname')->willReturn('Perez');
        $billingAddressMock->expects($this->once())->method('getEmail')->willReturn('juan.perez@example.com');
        $billingAddressMock->expects($this->once())->method('getTelephone')->willReturn('0900 2020');
        $billingAddressMock->expects($this->once())->method('getStreetLine')->with(1)->willReturn('50-51 China Street');
        $billingAddressMock->expects($this->once())->method('getCity')->willReturn('Hong Kong');
        $billingAddressMock->expects($this->once())->method('getCountryId')->willReturn('HK');

        $deliveryAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $deliveryAddressMock->expects($this->once())->method('getFirstname')->willReturn('Juan');
        $deliveryAddressMock->expects($this->once())->method('getLastname')->willReturn('Perez');
        $deliveryAddressMock->expects($this->once())->method('getStreetLine')->with(1)->willReturn('50-51 China Street');
        $deliveryAddressMock->expects($this->once())->method('getCity')->willReturn('Hong Kong');
        $deliveryAddressMock->expects($this->once())->method('getCountryId')->willReturn('HK');

        $requestHelperMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['populatePaymentAmountAndCurrency', 'getOrderDescription'])
            ->getMock();
        $requestHelperMock
            ->expects($this->once())
            ->method('getOrderDescription')
            ->with(false)
            ->willReturn("Online transaction.");
        $requestHelperMock
            ->expects($this->once())
            ->method('populatePaymentAmountAndCurrency')
            ->willReturn(
                [
                    "amount"   => 1500,
                    "currency" => "GBP"
                ]
            );

        $configMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['get3Dsecure','getSagepayPaymentAction', 'getAvsCvc'])
            ->getMock();
        $configMock->expects($this->once())->method('get3Dsecure')->with(false)->willReturn("UseMSPSetting");
        $configMock->expects($this->once())->method('getSagepayPaymentAction')->willReturn("Payment");
        $configMock->expects($this->once())->method('getAvsCvc')->willReturn("UseMSPSetting");

        $cartMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock->expects($this->once())->method('getBillingAddress')->willReturn($billingAddressMock);
        $cartMock->expects($this->once())->method('getShippingAddress')->willReturn($deliveryAddressMock);
        $cartMock->expects($this->once())->method('getIsVirtual')->willReturn(false);

        /** @var \Ebizmarts\SagePaySuite\Model\PiRequest $piRequestMock */
        $piRequestMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PiRequest::class)
            ->setConstructorArgs([$requestHelperMock, $configMock])
            ->setMethods(['getCart', 'getIsMoto'])
            ->getMock();

        $piRequestMock->setMerchantSessionKey("1EB6A6C0-47CF-4B88-90E2-FC0F31895AD8");
        $piRequestMock->setCardIdentifier("FE646772-6C9F-478B-BF11-9087105FD372");
        $piRequestMock->setVendorTxCode("000000194-2017-01-19-1351141484833874");

        $piRequestMock->expects($this->exactly(4))->method('getCart')->willReturn($cartMock);
        $piRequestMock->expects($this->exactly(3))->method('getIsMoto')->willReturn(false);

        $returnData = [
            'transactionType' => 'Payment',
            'paymentMethod'   => [
                'card'        => [
                    'merchantSessionKey' => "1EB6A6C0-47CF-4B88-90E2-FC0F31895AD8",
                    'cardIdentifier'     => "FE646772-6C9F-478B-BF11-9087105FD372",
                ]
            ],
            'vendorTxCode'      => "000000194-2017-01-19-1351141484833874",
            'description'       => "Online transaction.",
            'customerFirstName' => "Juan",
            'customerLastName'  => "Perez",
            'apply3DSecure'     => "UseMSPSetting",
            'applyAvsCvcCheck'  => "UseMSPSetting",
            'referrerId'        => "01bf51f9-0dcd-49dd-a07a-3b1f918c77d7",
            'customerEmail'     => "juan.perez@example.com",
            'customerPhone'     => "0900 2020",
            'entryMethod'       => "Ecommerce",
            'billingAddress'    => [
                                    'address1'   => "50-51 China Street",
                                    'city'       => "Hong Kong",
                                    'country'    => "HK",
                                    'postalCode' => "000"
            ],
            'shippingDetails'  => [
                                    "recipientFirstName" => "Juan",
                                    "recipientLastName"  => "Perez",
                                    "shippingAddress1"   => "50-51 China Street",
                                    "shippingCity"       => "Hong Kong",
                                    "shippingCountry"    => "HK",
                                    "shippingPostalCode"   => "000"
            ],
            'amount'           => 1500,
            'currency'         => 'GBP'
        ];

        $this->assertEquals($returnData, $piRequestMock->getRequestData());
    }

    public function testGetRequesDataUS()
    {
        $billingAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->expects($this->once())->method('getFirstname')->willReturn('Juan');
        $billingAddressMock->expects($this->once())->method('getLastname')->willReturn('Perez');
        $billingAddressMock->expects($this->once())->method('getEmail')->willReturn('juan.perez@example.com');
        $billingAddressMock->expects($this->once())->method('getTelephone')->willReturn('0900 2020');
        $billingAddressMock->expects($this->once())->method('getStreetLine')->with(1)->willReturn('10441 Jefferson Blvd Ste 200');
        $billingAddressMock->expects($this->once())->method('getCity')->willReturn('Culver City');
        $billingAddressMock->expects($this->once())->method('getPostcode')->willReturn('90232');
        $billingAddressMock->expects($this->once())->method('getCountryId')->willReturn('US');
        $billingAddressMock->expects($this->once())->method('getRegionCode')->willReturn('CA');

        $deliveryAddressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $deliveryAddressMock->expects($this->once())->method('getFirstname')->willReturn('Juan');
        $deliveryAddressMock->expects($this->once())->method('getLastname')->willReturn('Perez');
        $deliveryAddressMock->expects($this->once())->method('getStreetLine')->with(1)->willReturn('10441 Jefferson Blvd Ste 200');
        $deliveryAddressMock->expects($this->once())->method('getCity')->willReturn('Culver City');
        $deliveryAddressMock->expects($this->once())->method('getPostcode')->willReturn('90232');
        $deliveryAddressMock->expects($this->once())->method('getCountryId')->willReturn('US');
        $deliveryAddressMock->expects($this->once())->method('getRegionCode')->willReturn('CA');

        $requestHelperMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['populatePaymentAmountAndCurrency', 'getOrderDescription'])
            ->getMock();
        $requestHelperMock
            ->expects($this->once())
            ->method('getOrderDescription')
            ->with(false)
            ->willReturn("Online transaction.");
        $requestHelperMock
            ->expects($this->once())
            ->method('populatePaymentAmountAndCurrency')
            ->willReturn(
                [
                    "amount"   => 1500,
                    "currency" => "GBP"
                ]
            );

        $configMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['get3Dsecure','getSagepayPaymentAction', 'getAvsCvc'])
            ->getMock();
        $configMock->expects($this->once())->method('get3Dsecure')->with(false)->willReturn("UseMSPSetting");
        $configMock->expects($this->once())->method('getSagepayPaymentAction')->willReturn("Payment");
        $configMock->expects($this->once())->method('getAvsCvc')->willReturn("UseMSPSetting");

        $cartMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cartMock->expects($this->once())->method('getBillingAddress')->willReturn($billingAddressMock);
        $cartMock->expects($this->once())->method('getShippingAddress')->willReturn($deliveryAddressMock);
        $cartMock->expects($this->once())->method('getIsVirtual')->willReturn(false);

        /** @var \Ebizmarts\SagePaySuite\Model\PiRequest $piRequestMock */
        $piRequestMock = $this->getMockBuilder(\Ebizmarts\SagePaySuite\Model\PiRequest::class)
            ->setConstructorArgs([$requestHelperMock, $configMock])
            ->setMethods(['getCart', 'getIsMoto'])
            ->getMock();

        $piRequestMock->setMerchantSessionKey("1EB6A6C0-47CF-4B88-90E2-FC0F31895AD8");
        $piRequestMock->setCardIdentifier("FE646772-6C9F-478B-BF11-9087105FD372");
        $piRequestMock->setVendorTxCode("000000194-2017-01-19-1351141484833874");

        $piRequestMock->expects($this->exactly(4))->method('getCart')->willReturn($cartMock);
        $piRequestMock->expects($this->exactly(3))->method('getIsMoto')->willReturn(false);

        $returnData = [
            'transactionType' => 'Payment',
            'paymentMethod'   => [
                'card'        => [
                    'merchantSessionKey' => "1EB6A6C0-47CF-4B88-90E2-FC0F31895AD8",
                    'cardIdentifier'     => "FE646772-6C9F-478B-BF11-9087105FD372",
                ]
            ],
            'vendorTxCode'      => "000000194-2017-01-19-1351141484833874",
            'description'       => "Online transaction.",
            'customerFirstName' => "Juan",
            'customerLastName'  => "Perez",
            'apply3DSecure'     => "UseMSPSetting",
            'applyAvsCvcCheck'  => "UseMSPSetting",
            'referrerId'        => "01bf51f9-0dcd-49dd-a07a-3b1f918c77d7",
            'customerEmail'     => "juan.perez@example.com",
            'customerPhone'     => "0900 2020",
            'entryMethod'       => "Ecommerce",
            'billingAddress'    => [
                'address1'   => "10441 Jefferson Blvd Ste 200",
                'city'       => "Culver City",
                'postalCode' => "90232",
                'country'    => "US",
                'state'      => "CA",
            ],
            'shippingDetails'  => [
                "recipientFirstName" => "Juan",
                "recipientLastName"  => "Perez",
                "shippingAddress1"   => "10441 Jefferson Blvd Ste 200",
                "shippingCity"       => "Culver City",
                "shippingPostalCode" => "90232",
                "shippingCountry"    => "US",
                "shippingState"      => "CA",
            ],
            'amount'           => 1500,
            'currency'         => 'GBP'
        ];

        $this->assertEquals($returnData, $piRequestMock->getRequestData());
    }
}
