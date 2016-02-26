<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Model;

class FormTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sage Pay Transaction ID
     */
    const TEST_VPSTXID = 'F81FD5E1-12C9-C1D7-5D05-F6E8C12A526F';

    /**
     * Sage Pay response hash
     */
    const TEST_RESPONSE_HASH = '@77a9f5fb9cbfc11c6f3d5d6b424c7e84f7192396ae7b0a72f54bacd714f94548614ea82b977ef76cd4cb13fed5578af3bf64fa0801ce575d6b759bb53362f3898f711fb279cef7fe8ac1acdf147eec23dcfb04d0f68b2eb0130c450fcb91f78efb031b8522b4f31aba0f3e0820132ad50e0d2a68df717199e393f17450c608a19185307937ca5ad5b2b11fdf6e98b46ca45e7f8d6c2b2d4d063e5497ab56219eaa5019ed59aeea96a8d426fc34e5ccc4e45276842c4c986ee01d66d67cd9320bf76cba025bcff8dc6e7d980ba39067a5bdd82831294053060a5366d441c9818d2fa28bcddb41f0dc35901a52bb0885f7ef67af17c6a984546530d96be19132fcbf2dbe4459c8229b2dc9163944e7c7fa6e8392a907e860106696c04de947009383159e53c221a35a790b1fbcdd1fd24d2f162d28f85b06b6addf766b9dc0027ca189d8f2c4acff522a9dbffe42a19790ef2328a7cdfa60c24bb522e412430c2a7a6ab2063be6de5098f5e4ce352aa20956d17d32225cbb8fc1dad29e41470f26e2c72ad04762ea1e139331f8f3d9aab431f9d776d563124e16fde89726fd5582';

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form
     */
    protected $formModel;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sharedApiMock;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Crypt_AES|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cryptMock;

    protected function setUp()
    {
        $this->configMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock->expects($this->any())
            ->method('getFormEncryptedPassword')
            ->will($this->returnValue('dsa78dsa768dsa786dsa'));

        $this->sharedApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Shared')
            ->disableOriginalConstructor()
            ->getMock();

        $suiteHelperMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $suiteHelperMock->expects($this->any())
            ->method('clearTransactionId')
            ->will($this->returnValue(self::TEST_VPSTXID));

        $this->cryptMock = $this
            ->getMockBuilder('Crypt_AES')
            ->disableOriginalConstructor()
            ->getMock();
        $this->cryptMock->expects($this->any())
            ->method('decrypt')
            ->will($this->returnValue("Vendorname=testebizmarts&Status=OK&CcType=VISA"));

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->formModel = $objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Model\Form',
            [
                "config" => $this->configMock,
                "sharedApi" => $this->sharedApiMock,
                'suiteHelper' => $suiteHelperMock,
                "crypt" => $this->cryptMock
            ]
        );
    }

    public function testCapture()
    {
        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(1));
        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->with('paymentAction')
            ->will($this->returnValue(\Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER));

        $this->sharedApiMock->expects($this->once())
            ->method('releaseTransaction')
            ->with(1,100);

        $this->formModel->capture($paymentMock,100);
    }

    public function testCaptureERROR()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->will($this->returnValue(1000001));

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));
        $paymentMock->expects($this->any())
            ->method('getLastTransId')
            ->will($this->returnValue(2));
        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->with('paymentAction')
            ->will($this->returnValue(\Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE));

        $exception = new \Exception("Error in Authenticating");
        $this->sharedApiMock->expects($this->once())
            ->method('authorizeTransaction')
            ->with(2,100)
            ->willThrowException($exception);

        $response = "";
        try {
            $this->formModel->capture($paymentMock,100);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = $e->getMessage();
        }

        $this->assertEquals(
            'There was an error authorizing Sage Pay transaction 2: Error in Authenticating',
            $response
        );
    }

    public function testRefund()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->will($this->returnValue(1000001));

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));
        $paymentMock->expects($this->once())
            ->method('setIsTransactionClosed')
            ->with(1);
        $paymentMock->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with(1);

        $this->sharedApiMock->expects($this->once())
            ->method('refundTransaction')
            ->with(self::TEST_VPSTXID,100,1000001);

        $this->formModel->refund($paymentMock,100);

    }

    public function testRefundERROR()
    {
        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->will($this->returnValue(1000001));

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));

        $exception = new \Exception("Error in Refunding");
        $this->sharedApiMock->expects($this->once())
            ->method('refundTransaction')
            ->with(self::TEST_VPSTXID,100,1000001)
            ->willThrowException($exception);

        $response = "";
        try {
            $this->formModel->refund($paymentMock,100);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = $e->getMessage();
        }

        $this->assertEquals(
            'There was an error refunding Sage Pay transaction ' . self::TEST_VPSTXID . ': Error in Refunding',
            $response
        );

    }

    public function testDecodeSagePayResponse()
    {
        $this->assertEquals(
            [
                "Vendorname" => 'testebizmarts',
                "Status" => "OK",
                "CcType" => "VISA"
            ],
            $this->formModel->decodeSagePayResponse(self::TEST_RESPONSE_HASH)
        );

    }

    public function testGetConfigPaymentAction(){
        $this->configMock->expects($this->once())
            ->method('getPaymentAction');
        $this->formModel->getConfigPaymentAction();
    }
}
