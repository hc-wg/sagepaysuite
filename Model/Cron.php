<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Magento\Sales\Model\Order;
use \Ebizmarts\SagePaySuite\Model\Logger\Logger;
use \Magento\Sales\Api\TransactionRepositoryInterface;

class Cron
{

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface
     */
    private $_orderPaymentRepository;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
         * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $_orderCollectionFactory;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface;
     */
    private $_transactionRepository;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Fraud
     */
    private $_fraudHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud;
     */
    private $fraudModel;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * Cron constructor.
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Config $config
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param TransactionRepositoryInterface $transactionRepository
     * @param \Ebizmarts\SagePaySuite\Helper\Fraud $fraudHelper
     * @param ResourceModel\Fraud $fraudModel
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     */
    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Ebizmarts\SagePaySuite\Helper\Fraud $fraudHelper,
        \Ebizmarts\SagePaySuite\Model\ResourceModel\Fraud $fraudModel,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder
    ) {

        $this->_suiteLogger            = $suiteLogger;
        $this->_orderPaymentRepository = $orderPaymentRepository;
        $this->_objectManager          = $objectManager;
        $this->_config                 = $config;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_transactionRepository  = $transactionRepository;
        $this->_fraudHelper            = $fraudHelper;
        $this->fraudModel              = $fraudModel;
        $this->criteriaBuilder         = $criteriaBuilder;
        $this->filterBuilder           = $filterBuilder;
    }

    /**
     * Cancel Sage Pay orders in "pending payment" state after a period of time
     */
    public function cancelPendingPaymentOrders()
    {
        $orderIds = $this->fraudModel->getOrdersToCancel();

        if (!count($orderIds)) {
            return $this;
        }

        $orderCollection = $this->_orderCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => implode(',', $orderIds)])
            ->load();

        /** @var $_order \Magento\Sales\Model\Order */
        foreach ($orderCollection as $_order) {
            $orderId = $_order->getEntityId();

            try {
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                $payment = $_order->getPayment();
                if ($payment !== null && !empty($payment->getLastTransId())) {
                    if ($this->transactionIsPayment($payment->getLastTransId()) === false) {
                        /**
                         * CANCEL ORDER AS THERE IS NO TRANSACTION ASSOCIATED
                         */
                        $_order->cancel()->save(); //@codingStandardsIgnoreLine

                        $this->_suiteLogger->sageLog(
                            Logger::LOG_CRON,
                            ["OrderId" => $orderId,
                                "Result" => "CANCELLED : No payment received."]
                        );
                    } else {
                        $this->_suiteLogger->sageLog(
                            Logger::LOG_CRON,
                            ["OrderId" => $orderId,
                                "Result" => "ERROR : Transaction found: " . $payment->getLastTransId()]
                        );
                    }
                } else {
                    $this->_suiteLogger->sageLog(
                        Logger::LOG_CRON,
                        ["OrderId" => $orderId,
                            "Result" => "ERROR : No payment found."]
                    );
                }
            } catch (ApiException $apiException) {
                $this->_suiteLogger->sageLog(
                    Logger::LOG_CRON,
                    [
                        "OrderId" => $orderId,
                        "Result"  => $apiException->getUserMessage(),
                        "Stack"   => $apiException->getTraceAsString()
                    ]
                );
            } catch (\Exception $e) {
                $this->_suiteLogger->sageLog(
                    Logger::LOG_CRON,
                    [
                        "OrderId" => $orderId,
                        "Result"  => $e->getMessage(),
                        "Trace"   => $e->getTraceAsString()
                    ]
                );
            }
        }
    }

    /**
     * Check transaction fraud result and action based on result
     * @throws LocalizedException
     */
    public function checkFraud()
    {
        $transactions = $this->fraudModel->getShadowPaidPaymentTransactions();

        foreach ($transactions as $_transaction) {
            try {
                $transaction = $this->_transactionRepository->get($_transaction["transaction_id"]);
                $logData = [];

                $payment = $this->_orderPaymentRepository->get($transaction->getPaymentId());
                if ($payment === null) {
                    throw new \LocalizedException(__('Payment not found for this transaction'));
                }

                //process fraud information
                $logData = $this->_fraudHelper->processFraudInformation($transaction, $payment);
            } catch (ApiException $apiException) {
                $logData["ERROR"] = $apiException->getUserMessage();
                $logData["Trace"] = $apiException->getTraceAsString();
            } catch (\Exception $e) {
                $logData["ERROR"] = $e->getMessage();
                $logData["Trace"] = $e->getTraceAsString();
            }

            //log
            $this->_suiteLogger->sageLog(Logger::LOG_CRON, $logData);
        }
    }

    private function transactionIsPayment($vpsTxId)
    {
        /** @var \Magento\Sales\Api\Data\TransactionSearchResultInterface $transactions */
        $transactions = $this->_transactionRepository->getList(
            $this->searchCriteriaBuilderTxnId($vpsTxId)
        );

        return count($transactions->getItems()) === 1;
    }

    private function searchCriteriaBuilderTxnId($id)
    {
        $this->criteriaBuilder->addFilters(
            [$this->filterBuilder->setField('txn_id')->setValue($id)->setConditionType('eq')->create()]
        );

        return $this->criteriaBuilder->create();
    }
}
