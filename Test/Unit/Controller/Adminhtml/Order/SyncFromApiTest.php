<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Test\Unit\Controller\Adminhtml\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class SyncFromApiTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    private $objectManagerHelper;

    // @codingStandardsIgnoreStart
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
    }
    // @codingStandardsIgnoreEnd

    public function testExecute()
    {
        $redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $messageManagerMock->expects($this->any())
            ->method('addSuccess')
            ->with(__('Successfully synced from Sage Pay\'s API'));

        $requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->any())
            ->method('getParam')
            ->will($this->returnValue(1));

        $urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $actionFlagMock = $this
            ->getMockBuilder('Magento\Framework\App\ActionFlag')
            ->disableOriginalConstructor()
            ->getMock();

        $sessionMock = $this
            ->getMockBuilder('Magento\Backend\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock = $this
            ->getMockBuilder('Magento\Backend\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Backend\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($responseMock));
        $contextMock->expects($this->any())
            ->method('getRedirect')
            ->will($this->returnValue($redirectMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($requestMock));
        $contextMock->expects($this->any())
            ->method('getBackendUrl')
            ->will($this->returnValue($urlBuilderMock));
        $contextMock->expects($this->any())
            ->method('getActionFlag')
            ->will($this->returnValue($actionFlagMock));
        $contextMock->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($sessionMock));
        $contextMock->expects($this->any())
            ->method('getHelper')
            ->will($this->returnValue($helperMock));

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->setMethods(['getLastTransId', 'setAdditionalInformation', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->expects($this->atLeastOnce())
            ->method('setAdditionalInformation')
            ->willReturnSelf();
        $paymentMock
            ->expects($this->exactly(3))
            ->method('getLastTransId')
            ->willReturn('463B3DE6-443F-585B-E75C-C727476DE98F');

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

        $orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderMock));

        $reportingApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Reporting')
            ->disableOriginalConstructor()
            ->getMock();
        $reportingApiMock->expects($this->once())
            ->method('getTransactionDetails')
            ->will($this->returnValue((object)[
                "vendortxcode" => "100000001-2016-12-12-123456",
                "status" => "OK STATUS",
                "threedresult" => "CHECKED"
            ]));

        $trnRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment\Transaction\Repository::class)
            ->setMethods(['getSagepaysuiteFraudCheck', 'getByTransactionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $trnRepoMock
            ->expects($this->once())
            ->method('getSagepaysuiteFraudCheck')
            ->willReturn(false);
        $trnRepoMock
            ->expects($this->once())
            ->method('getByTransactionId')
            ->willReturnSelf();

        $fraudHelperMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Fraud::class)
            ->setMethods(['processFraudInformation'])
            ->disableOriginalConstructor()
            ->getMock();
        $fraudHelperMock->expects($this->once())->method('processFraudInformation');

        $syncFromApiController = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Adminhtml\Order\SyncFromApi',
            [
                'context'               => $contextMock,
                'orderFactory'          => $orderFactoryMock,
                'reportingApi'          => $reportingApiMock,
                'transactionRepository' => $trnRepoMock,
                'fraudHelper'           => $fraudHelperMock
            ]
        );

        $syncFromApiController->execute();
    }

    public function testExecuteNoParam()
    {
        $requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('order_id')
            ->willReturn(null);

        $actionFlagMock = $this
            ->getMockBuilder('Magento\Framework\App\ActionFlag')
            ->disableOriginalConstructor()
            ->getMock();

        $sessionMock = $this
            ->getMockBuilder('Magento\Backend\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $messageManagerMock->expects($this->once())
            ->method('addError')
            ->with('Something went wrong: Unable to sync from API: Invalid order id.');

        $urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $urlBuilderMock->expects($this->once())->method('getUrl')->with('sales/order/index/', []);

        $helperMock = $this
            ->getMockBuilder('Magento\Backend\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Backend\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getActionFlag')
            ->will($this->returnValue($actionFlagMock));
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($requestMock);
        $contextMock->expects($this->any())
            ->method('getBackendUrl')
            ->will($this->returnValue($urlBuilderMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));
        $contextMock->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($sessionMock));
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this
                ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
                ->disableOriginalConstructor()
                ->getMock()));
        $contextMock->expects($this->any())
            ->method('getHelper')
            ->will($this->returnValue($helperMock));

        $controller = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Adminhtml\Order\SyncFromApi',
            [
                'context' => $contextMock,
            ]
        );

        $controller->execute();
    }

    public function testExecuteApiException()
    {
        $redirectMock = $this->getMockForAbstractClass('Magento\Framework\App\Response\RedirectInterface');

        $responseMock = $this
            ->getMockBuilder('Magento\Framework\App\Response\Http', [], [], '', false)
            ->disableOriginalConstructor()
            ->getMock();

        $messageManagerMock = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $messageManagerMock->expects($this->once())
            ->method('addError')
            ->with('The user does not have permission to view this transaction.');

        $requestMock = $this
            ->getMockBuilder('Magento\Framework\HTTP\PhpEnvironment\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(5899);

        $urlBuilderMock = $this
            ->getMockBuilder('Magento\Framework\UrlInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $urlBuilderMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('sales/order/view/', ['order_id' => 5899]);

        $actionFlagMock = $this
            ->getMockBuilder('Magento\Framework\App\ActionFlag')
            ->disableOriginalConstructor()
            ->getMock();

        $sessionMock = $this
            ->getMockBuilder('Magento\Backend\Model\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $helperMock = $this
            ->getMockBuilder('Magento\Backend\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder('Magento\Backend\App\Action\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($responseMock));
        $contextMock->expects($this->any())
            ->method('getRedirect')
            ->will($this->returnValue($redirectMock));
        $contextMock->expects($this->any())
            ->method('getMessageManager')
            ->will($this->returnValue($messageManagerMock));
        $contextMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($requestMock));
        $contextMock->expects($this->any())
            ->method('getBackendUrl')
            ->will($this->returnValue($urlBuilderMock));
        $contextMock->expects($this->any())
            ->method('getActionFlag')
            ->will($this->returnValue($actionFlagMock));
        $contextMock->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($sessionMock));
        $contextMock->expects($this->any())
            ->method('getHelper')
            ->will($this->returnValue($helperMock));

        $paymentMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->setMethods(['getLastTransId', 'setAdditionalInformation', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock
            ->expects($this->never())
            ->method('setAdditionalInformation');
        $paymentMock
            ->expects($this->once())
            ->method('getLastTransId')
            ->willReturn('463B3DE6-443F-585B-E75C-C727476DE98F');

        $orderMock = $this
            ->getMockBuilder('Magento\Sales\Model\Order')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));
        $orderMock->expects($this->once())
            ->method('getId')
            ->willReturn(5899);

        $orderFactoryMock = $this
            ->getMockBuilder('Magento\Sales\Model\OrderFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderMock));

        $reportingApiMock = $this
            ->getMockBuilder('Ebizmarts\SagePaySuite\Model\Api\Reporting')
            ->disableOriginalConstructor()
            ->getMock();

        $error     = new \Magento\Framework\Phrase("The user does not have permission to view this transaction.");
        $exception = new \Ebizmarts\SagePaySuite\Model\Api\ApiException($error);

        $reportingApiMock->expects($this->once())
            ->method('getTransactionDetails')
            ->willThrowException($exception);

        $trnRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment\Transaction\Repository::class)
            ->setMethods(['getSagepaysuiteFraudCheck', 'getByTransactionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $trnRepoMock
            ->expects($this->never())
            ->method('getSagepaysuiteFraudCheck');
        $trnRepoMock
            ->expects($this->never())
            ->method('getByTransactionId')
            ->willReturnSelf();

        $fraudHelperMock = $this
            ->getMockBuilder(\Ebizmarts\SagePaySuite\Helper\Fraud::class)
            ->setMethods(['processFraudInformation'])
            ->disableOriginalConstructor()
            ->getMock();
        $fraudHelperMock->expects($this->never())->method('processFraudInformation');

        $syncFromApiController = $this->objectManagerHelper->getObject(
            'Ebizmarts\SagePaySuite\Controller\Adminhtml\Order\SyncFromApi',
            [
                'context'               => $contextMock,
                'orderFactory'          => $orderFactoryMock,
                'reportingApi'          => $reportingApiMock,
                'transactionRepository' => $trnRepoMock,
                'fraudHelper'           => $fraudHelperMock
            ]
        );

        $syncFromApiController->execute();
    }
}
