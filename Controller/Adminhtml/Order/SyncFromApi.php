<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\Order;

use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Validator\Exception as ValidatorException;

class SyncFromApi extends \Magento\Backend\App\AbstractAction
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Reporting
     */
    private $_reportingApi;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Fraud
     */
    private $_fraudHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\Repository
     */
    private $_transactionRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Api\Reporting $reportingApi,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Fraud $fraudHelper,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository
    ) {
    
        parent::__construct($context);
        $this->_reportingApi          = $reportingApi;
        $this->_orderFactory          = $orderFactory;
        $this->_suiteLogger           = $suiteLogger;
        $this->_fraudHelper           = $fraudHelper;
        $this->_suiteHelper           = $suiteHelper;
        $this->_transactionRepository = $transactionRepository;
    }

    public function execute()
    {
        try {
            //get order id
            if (!empty($this->getRequest()->getParam("order_id"))) {
                $order = $this->_orderFactory->create()->load($this->getRequest()->getParam("order_id"));
                $payment = $order->getPayment();
            } else {
                throw new ValidatorException(__('Unable to sync from API: Invalid order id.'));
            }

            $transactionId = $this->_suiteHelper->clearTransactionId($payment->getLastTransId());
            $transactionDetails = $this->_reportingApi->getTransactionDetails($transactionId, $order->getStoreId());

            $payment->setAdditionalInformation('vendorTxCode', (string)$transactionDetails->vendortxcode);
            $payment->setAdditionalInformation('statusDetail', (string)$transactionDetails->status);
            if (isset($transactionDetails->{'threedresult'})) {
                $payment->setAdditionalInformation('threeDStatus', (string)$transactionDetails->{'threedresult'});
            }
            $payment->save();

            //update fraud status
            if (!empty($payment->getLastTransId())) {
                $transaction = $this->_transactionRepository
                                ->getByTransactionId($payment->getLastTransId(), $payment->getId(), $order->getId());
                if ($transaction !== false && $this->isFraudNotChecked($transaction)) {
                    $this->_fraudHelper->processFraudInformation($transaction, $payment);
                }
            }

            $this->messageManager->addSuccess(__('Successfully synced from Sage Pay\'s API'));
        } catch (ApiException $apiException) {
            $this->_suiteLogger->sageLog(Logger::LOG_EXCEPTION, $apiException->getTraceAsString(), [__METHOD__, __LINE__]);
            $this->messageManager->addError(__($apiException->getUserMessage()));
        } catch (\Exception $e) {
            $this->_suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getTraceAsString(), [__METHOD__, __LINE__]);
            $this->messageManager->addError(__('Something went wrong: %1', $e->getMessage()));
        }

        if (!empty($order)) {
            $this->_redirect($this->_backendUrl->getUrl('sales/order/view/', ['order_id' => $order->getId()]));
        } else {
            $this->_redirect($this->_backendUrl->getUrl('sales/order/index/', []));
        }
    }

    /**
     * @param $transaction
     * @return bool
     */
    private function isFraudNotChecked($transaction)
    {
        return (bool)$transaction->getSagepaysuiteFraudCheck() === false;
    }
}
