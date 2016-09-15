<?php


namespace Ebizmarts\SagePaySuite\Observer;

use Magento\Framework\Event\ObserverInterface;

class CreditMemo implements ObserverInterface
{
    private $_suiteHelper;
    private $_suiteReportingApi;
    private $_messageManager;

    public function __construct(\Magento\Framework\Message\ManagerInterface $messageManager, \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper, \Ebizmarts\SagePaySuite\Model\Api\Reporting $reportingApi)
    {
        $this->_suiteHelper       = $suiteHelper;
        $this->_suiteReportingApi = $reportingApi;
        $this->_messageManager    = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment           = $observer->getData('creditmemo')->getOrder()->getPayment();
        $paymentMethodCode = $payment->getMethod();

        if(!$this->_suiteHelper->methodCodeIsSagePay($paymentMethodCode)) {
            return;
        }

        $vpsTxIdRaw = $observer->getData('creditmemo')->getOrder()->getPayment()->getLastTransId();
        $vpsTxId    = $this->_suiteHelper->clearTransactionId($vpsTxIdRaw);

        try {
            $this->_suiteReportingApi->getTransactionDetails($vpsTxId);
        }catch(\Exception $e) {
            $this->_messageManager->addErrorMessage(__("This Sage Pay transaction cannot be refunded online because the Reporting API communication could not be established. The response is: %1", $e->getMessage()));
        }

    }
}