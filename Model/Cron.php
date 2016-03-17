<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Store\Model\Store;
use Magento\Sales\Model\Order;

class Cron
{

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface
     */
    protected $_orderPaymentRepository;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Fraud
     */
    protected $_fraudHelper;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param Logger\Logger $suiteLogger
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
    )
    {
        $this->_resource = $resource;
        $this->_suiteLogger = $suiteLogger;
        $this->_orderPaymentRepository = $orderPaymentRepository;
        $this->_objectManager = $objectManager;
        $this->_config = $config;
        $this->_orderFactory = $orderFactory;
        $this->_transactionFactory = $transactionFactory;
        $this->_fraudHelper = $fraudHelper;
    }

    /**
     * Cancel Sage Pay orders in "pending payment" state after a period of time
     */
    public function cancelPendingPaymentOrders()
    {
        //$this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON, "Running cancel pending payments...");

        $ordersTableName = $this->_resource->getTableName('sales_order');
        $connection = $this->_resource->getConnection();

        $select = $connection->select()
            ->from($ordersTableName)
            ->where(
                'state=?',
                \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
            )->where(
                'created_at <= now() - INTERVAL 15 MINUTE'
            )->where(
                'created_at >= now() - INTERVAL 2 DAY'
            )->limit(10);

        foreach ($connection->fetchAll($select) as $orderArray) {

            $order = $this->_orderFactory->create()->load($orderArray["entity_id"]);
            $orderId = $order->getEntityId();

            try {
                $payment = $order->getPayment();
                if (!is_null($payment) && !empty($payment->getLastTransId())) {

                    $transaction = $this->_transactionFactory->create()->load($payment->getLastTransId());
                    if (empty($transaction->getId())) {
                        /**
                         * CANCEL ORDER AS THERE IS NO TRANSACTION ASSOCIATED
                         */
                        $order->cancel()->save();

                        $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                            array("OrderId" => $orderId,
                                "Result" => "CANCELLED : No payment received.")
                        );
                    } else {

                        $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                            array("OrderId" => $orderId,
                                "Result" => "ERROR : Transaction found: " . $transaction->getTxnId())
                        );
                    }
                } else {
                    $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                        array("OrderId" => $orderId,
                            "Result" => "ERROR : No payment found.")
                    );
                }
            } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
                $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                    array("OrderId" => $orderId,
                        "Result" => $apiException->getUserMessage())
                );

            } catch (\Exception $e) {
                $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                    array("OrderId" => $orderId,
                        "Result" => $e->getMessage())
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

        foreach ($connection->fetchAll($select) as $trnArray) {

            $transaction = $this->_transactionFactory->create()->load($trnArray["transaction_id"]);
            $logData = array();

            try {

                $payment = $this->_orderPaymentRepository->get($transaction->getPaymentId());
                if (is_null($payment)) {
                    throw new LocalizedException(__('Payment not found for this transaction'));
                }

                //process fraud information
                $logData = $this->_fraudHelper->processFraudInformation($transaction,$payment);

            } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
                $logData["ERROR"] = $apiException->getUserMessage();

            } catch (\Exception $e) {
                $logData["ERROR"] = $e->getMessage();
            }

            //log
            $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON, $logData);
        }
    }
}