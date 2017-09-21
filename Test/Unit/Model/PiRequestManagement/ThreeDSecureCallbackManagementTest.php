<?php

namespace Ebizmarts\SagePaySuite\Test\Unit\Model\PiRequestManagement;

class ThreeDSecureCallbackManagementTest extends \PHPUnit_Framework_TestCase
{

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

        $piTransactionResult = $objectManagerHelper->getObject("Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult");
        //$piTransactionResult->setData([]);

        $piTransactionResultFactoryMock = $this->getMockBuilder("Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory")
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
            ->disableOriginalConstructor()->getMock();
        $checkoutSessionMock->expects($this->never())->method("setData");

        /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement $model */
        $model = $objectManagerHelper->getObject(
            "Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement",
            [
                "checkoutHelper" => $checkoutHelperMock,
                "payResultFactory" => $piTransactionResultFactoryMock,
                "piRestApi" => $piRestApiMock,
                "checkoutSession" => $checkoutSessionMock
            ]
        );

        $requestData = $objectManagerHelper->getObject("Ebizmarts\SagePaySuite\Api\Data\PiRequestManager");
        $model->setRequestData($requestData);

        $payResult = $objectManagerHelper->getObject("Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResult");
        $model->setPayResult($payResult);

        $model->placeOrder();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeThreeDMock(): \PHPUnit_Framework_MockObject_MockObject
    {
        $model = $this->getMockBuilder("Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement")
            ->disableOriginalConstructor()
            ->setMethods(["getPayment"])
            ->getMock();

        return $model;
    }

}