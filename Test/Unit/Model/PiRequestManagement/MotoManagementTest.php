<?php
/**
 * Created by PhpStorm.
 * User: pablo
 * Date: 4/12/18
 * Time: 2:01 PM
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

use Ebizmarts\SagePaySuite\Model\PiRequestManagement\MotoManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class MotoManagementTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    private $objectManagerHelper;

    const TEST_ORDER_NUMBER = 7832;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManager($this);
    }

    public function testIsMotoTransaction()
    {
        /** @var MotoManagement $sut */
        $sut = $this->objectManagerHelper->getObject(MotoManagement::class);

        $this->assertTrue($sut->getIsMotoTransaction());
    }

    public function testPlaceOrder()
    {
        $checkoutHelperMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Helper\Checkout::class);

        $quoteMock = $this->makeMockDisabledConstructor(\Magento\Quote\Model\Quote::class);
        $quoteMock->expects($this->once())->method('collectTotals')->willReturnSelf();
        $quoteMock->expects($this->once())->method('reserveOrderId')->willReturnSelf();

        $requestDataMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerInterface::class);

        $payResultMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface::class);
        $payResultMock->expects($this->any())->method('getStatusCode')->willReturn(\Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS);

        $piRestApiMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\Api\PIRest::class);
        $piRestApiMock->expects($this->once())->method('capture')->willReturn($payResultMock);

        $sageCardTypeMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\Config\SagePayCardType::class);

        $piRequestMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\PiRequest::class);
        $piRequestMock->expects($this->once())->method('setCart')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setMerchantSessionKey')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setCardIdentifier')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setVendorTxCode')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('setIsMoto')->willReturnSelf();
        $piRequestMock->expects($this->once())->method('getRequestData')->willReturn([]);

        $suiteHelperMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Helper\Data::class);

        $piResultMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Api\Data\PiResultInterface::class);

        $motoOrderCreateModelMock = $this->getMockBuilder(\Magento\Sales\Model\AdminOrder\Create::class)
            ->disableOriginalConstructor()
            ->getMock();
        $motoOrderCreateModelMock->expects($this->once())->method('setIsValidate')->with(true)->willReturnSelf();
        $motoOrderCreateModelMock->expects($this->once())->method('importPostData')/*->with(true)*/->willReturnSelf();
        $motoOrderCreateModelMock->expects($this->any())->method('__call')->with(
            $this->equalTo('setSendConfirmation'),
            $this->equalTo([0])
        )->willReturnSelf();
        $motoOrderCreateModelMock->expects($this->once())->method('createOrder')->willReturnSelf();
//        $motoOrderCreateModelMock->expects($this->any())->method('__call')->with('getId')->willReturn(self::TEST_ORDER_NUMBER);

        $objectManagerMock = $this->makeMockDisabledConstructor(\Magento\Framework\ObjectManagerInterface::class);
        $objectManagerMock->expects($this->once())->method('get')->with('Magento\Sales\Model\AdminOrder\Create')
            ->willReturn($motoOrderCreateModelMock);

        $requestMock = $this->makeMockDisabledConstructor(\Magento\Framework\App\Request\Http::class);
        $requestMock->expects($this->exactly(2))->method('getPost')
            ->withConsecutive(['order'], ['payment'])
            ->willReturnOnConsecutiveCalls([], []);

        $urlMock = $this->makeMockDisabledConstructor(\Magento\Backend\Model\UrlInterface::class);
        $urlMock->expects($this->once())->method('getUrl')->with('sales/order/views', ['order_id' => self::TEST_ORDER_NUMBER]);

        $loggerMock = $this->makeMockDisabledConstructor(\Ebizmarts\SagePaySuite\Model\Logger\Logger::class);

        $emailSenderMock = $this->makeMockDisabledConstructor(\Magento\Sales\Model\AdminOrder\EmailSender::class);
        $emailSenderMock->expects($this->once())->method('send');

        /** @var MotoManagement $sut */
        $sut = $this->objectManagerHelper->getObject(
            MotoManagement::class,
            [
                'checkoutHelper' => $checkoutHelperMock,
                'piRestApi' => $piRestApiMock,
                'ccConvert' => $sageCardTypeMock,
                'piRequest' => $piRequestMock,
                'suiteHelper' => $suiteHelperMock,
                'result' => $piResultMock,
                'objectManager' => $objectManagerMock,
                'httpRequest' => $requestMock,
                'backendUrl' => $urlMock,
                'suiteLogger' => $loggerMock,
                'emailSender' => $emailSenderMock
            ]
        );

        $sut->setQuote($quoteMock);
        $sut->setRequestData($requestDataMock);

        $sut->placeOrder();
    }

    /**
     * @param string $class
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeMockDisabledConstructor($class)
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

}