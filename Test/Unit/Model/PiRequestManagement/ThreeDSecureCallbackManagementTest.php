<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

class ThreeDSecureCallbackManagementTest extends \PHPUnit\Framework\TestCase
{

    const THREE_D_SECURE_CALLBACK_MANAGEMENT = "Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement";

    const PI_TRANSACTION_RESULT_FACTORY = "Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory";

    const PI_TRANSACTION_RESULT = "Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult";

    public function testIsNotMotoTransaction()
    {
        /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $model */
        $model = $this->makeThreeDMock();

        $this->assertFalse($model->getIsMotoTransaction());
    }

    /**
     * @expectedException \LogicException
     */
    public function testPlaceOrder()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $checkoutHelperMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Helper\Checkout")
            ->disableOriginalConstructor()->getMock();

        $piTransactionResult = $objectManagerHelper->getObject(self::PI_TRANSACTION_RESULT);
        //$piTransactionResult->setData([]);

        $piTransactionResultFactoryMock = $this->getMockBuilder(self::PI_TRANSACTION_RESULT_FACTORY)
            ->setMethods(["create"])->disableOriginalConstructor()->getMock();
        $piTransactionResultFactoryMock->method("create")->willReturn($piTransactionResult);

        /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD $threeDResult */
        $threeDResult = $objectManagerHelper->getObject("Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD");
        $threeDResult->setStatus("Authenticated");

        $piRestApiMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Model\Api\PIRest")
            ->disableOriginalConstructor()->getMock();
        $piRestApiMock->expects($this->once())->method("submit3D")->willReturn($threeDResult);

        $error     = new \Magento\Framework\Phrase("Transaction not found.");
        $exception = new \Ebizmarts\SagePaySuite\Model\Api\ApiException($error);
        $piRestApiMock->expects($this->exactly(5))->method("transactionDetails")->willThrowException($exception);
        $piRestApiMock->expects($this->once())->method("void");

        $checkoutSessionMock = $this->getMockBuilder("Magento\Checkout\Model\Session")
            ->setMethods(["setData"])
            ->disableOriginalConstructor()->getMock();
        $checkoutSessionMock->expects($this->never())->method("setData");

        /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $model */
        $model = $objectManagerHelper->getObject(
            self::THREE_D_SECURE_CALLBACK_MANAGEMENT,
            [
                "checkoutHelper" => $checkoutHelperMock,
                "payResultFactory" => $piTransactionResultFactoryMock,
                "piRestApi" => $piRestApiMock,
                "checkoutSession" => $checkoutSessionMock
            ]
        );

        $requestData = $objectManagerHelper->getObject("Ebizmarts\SagePaySuite\Api\Data\PiRequestManager");
        $model->setRequestData($requestData);

        $payResult = $objectManagerHelper->getObject(self::PI_TRANSACTION_RESULT);
        $model->setPayResult($payResult);

        $model->placeOrder();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeThreeDMock()
    {
        $model = $this->getMockBuilder(self::THREE_D_SECURE_CALLBACK_MANAGEMENT)
            ->disableOriginalConstructor()
            ->setMethods(["getPayment"])
            ->getMock();

        return $model;
    }

}