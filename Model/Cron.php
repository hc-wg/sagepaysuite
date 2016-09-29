<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Magento\Store\Model\Store;
use Magento\Sales\Model\Order;
use \Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Cron
{

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $_resource;

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
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    private $_transactionFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Fraud
     */
    private $_fraudHelper;

    /**
     * Cron constructor.
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Config $config
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param Order\Payment\TransactionFactory $transactionFactory
     * @param \Ebizmarts\SagePaySuite\Helper\Fraud $fraudHelper
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Ebizmarts\SagePaySuite\Helper\Fraud $fraudHelper
    ) {

        $this->_resource               = $resource;
        $this->_suiteLogger            = $suiteLogger;
        $this->_orderPaymentRepository = $orderPaymentRepository;
        $this->_objectManager          = $objectManager;
        $this->_config                 = $config;
        $this->_orderFactory           = $orderFactory;
        $this->_transactionFactory     = $transactionFactory;
        $this->_fraudHelper            = $fraudHelper;
    }

    /**
     * Cancel Sage Pay orders in "pending payment" state after a period of time
     */
    public function cancelPendingPaymentOrders()
    {
        $ordersTableName = $this->_resource->getTableName('sales_order');
        $connection      = $this->_resource->getConnection();

        $select = $connection->select()
            ->from($ordersTableName)
            ->where(
                'state=?',
                Order::STATE_PENDING_PAYMENT
            )
            ->where(
                'created_at <= now() - INTERVAL 15 MINUTE'
            )->where(
                'created_at >= now() - INTERVAL 2 DAY'
            )
            ->limit(10);

        $query = $connection->query($select);

        while ($row = $query->fetch()) {
            $order   = $this->_orderFactory->create()->load($row["entity_id"]);
            $orderId = $order->getEntityId();

            try {
                $payment = $order->getPayment();
                if ($payment !== null && !empty($payment->getLastTransId())) {
                    $transaction = $this->_transactionFactory->create()->load($payment->getLastTransId());
                    if (empty($transaction->getId())) {
                        /**
                         * CANCEL ORDER AS THERE IS NO TRANSACTION ASSOCIATED
                         */
                        $order->cancel()->save();

                        $this->_suiteLogger->sageLog(
                            Logger::LOG_CRON,
                            ["OrderId" => $orderId,
                                "Result" => "CANCELLED : No payment received."]
                        );
                    } else {
                        $this->_suiteLogger->sageLog(
                            Logger::LOG_CRON,
                            ["OrderId" => $orderId,
                                "Result" => "ERROR : Transaction found: " . $transaction->getTxnId()]
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
                    ["OrderId" => $orderId,
                        "Result" => $apiException->getUserMessage()]
                );
            } catch (\Exception $e) {
                $this->_suiteLogger->sageLog(
                    Logger::LOG_CRON,
                    ["OrderId" => $orderId,
                        "Result" => $e->getMessage()]
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
        $transactionTableName = $this->_resource->getTableName('sales_payment_transaction');
        $connection = $this->_resource->getConnection();

        $select = $connection->select()
            ->from($transactionTableName)
            ->where(
                'sagepaysuite_fraud_check=0'
            )->where(
                "txn_type='" . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE .
                "' OR txn_type='" . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH . "'"
            )->where(
                'parent_id IS NULL'
            )->where(
                'created_at >= now() - INTERVAL 2 DAY'
            )->limit(20);

        $query = $connection->query($select);

        while ($row = $query->fetch()) {
            $transaction = $this->_transactionFactory->create()->load($row["transaction_id"]);
            $logData = [];

            try {
                $payment = $this->_orderPaymentRepository->get($transaction->getPaymentId());
                if ($payment === null) {
                    throw new \LocalizedException(__('Payment not found for this transaction'));
                }

                //process fraud information
                $logData = $this->_fraudHelper->processFraudInformation($transaction, $payment);
            } catch (ApiException $apiException) {
                $logData["ERROR"] = $apiException->getUserMessage();
            } catch (\Exception $e) {
                $logData["ERROR"] = $e->getMessage();
            }

            //log
            $this->_suiteLogger->sageLog(Logger::LOG_CRON, $logData);
        }
    }
}
