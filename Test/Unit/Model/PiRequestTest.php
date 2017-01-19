<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 1/19/17
 * Time: 11:19 AM
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;


class PiRequestTest extends \PHPUnit_Framework_TestCase
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
            ->setMethods(['populatePaymentAmount', 'getOrderDescription'])
            ->getMock();
        $requestHelperMock
            ->expects($this->once())
            ->method('getOrderDescription')
            ->with(false)
            ->willReturn("Online transaction.");
        $requestHelperMock
            ->expects($this->once())
            ->method('populatePaymentAmount')
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

}