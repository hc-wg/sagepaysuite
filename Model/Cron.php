<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

class Cron
{

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * \Ebizmarts\SagePaySuite\Model\Api\Transaction
     */
    protected $_transactionsApi;

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
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Ebizmarts\SagePaySuite\Model\Api\Transaction $transactionsApi,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_resource = $resource;
        $this->_transactionsApi = $transactionsApi;
        $this->_suiteLogger = $suiteLogger;
        $this->_orderPaymentRepository = $orderPaymentRepository;
        $this->_objectManager = $objectManager;
    }

    public function cancelPendingPaymentOrders()
    {

        //$this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON, "Running cancel pending payments...");

        $ordersTableName = $this->_resource->getTableName('sales_order');
        $connection = $this->_resource->getConnection();

        $select = $connection->select()->from($ordersTableName)
            ->where(
                'state=?',
                \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT
            )->where(
                'created_at <= now() - INTERVAL 15 MINUTE'
            )->where(
                'created_at >= now() - INTERVAL 2 DAY'
            )->limit(10);

        foreach ($connection->fetchAll($select) as $orderArray) {

            $order = $this->_objectManager->get('\Magento\Sales\Model\Order')->load($orderArray["entity_id"]);

            $orderId = $order->getEntityId();

            try {
                $payment = $order->getPayment();
                if (!is_null($payment) && !empty($payment->getLastTransId())) {

                    $transaction = $this->_objectManager->get('\Magento\Sales\Model\Order\Payment\Transaction')->load($payment->getLastTransId());
                    if(empty($transaction->getId())){

                        /**
                         * CANCEL ORDER AS THERE IS NO TRANSACTION ASSOCIATED
                         */
                        $order->cancel()->save();

                        $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                            array("OrderId" => $orderId,
                                "Result" => "CANCELLED : No payment received.")
                        );
                    }else{

                        $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                            array("OrderId" => $orderId,
                                "Result" => "ERROR : Transaction found: " . $transaction->getTxnId())
                        );
                    }
                }else{
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

    public function checkFraud()
    {
        //$this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON, "Running Fraud checks...");

        $transactionTableName = $this->_resource->getTableName('sales_payment_transaction');
        $connection = $this->_resource->getConnection();

        $select = $connection->select()->from($transactionTableName)
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

            $transaction = $this->_objectManager->get('\Magento\Sales\Model\Order\Payment\Transaction')->load($trnArray["transaction_id"]);

            //$this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,$transaction);

            try {
                $sagepayVpsTxId = $transaction->getTxnId();
                $payment = $this->_orderPaymentRepository->get($transaction->getPaymentId());
                if (is_null($payment)) {
                    throw new LocalizedException(__('Payment not found for this transaction'));
                }

                //flag test transactions
                if ($payment->getAdditionalInformation("mode") &&
                    $payment->getAdditionalInformation("mode") == \Ebizmarts\SagePaySuite\Model\Config::MODE_TEST
                ) {
                    $transaction->setSagepaysuiteFraudCheck(1)->save();

                    $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                        array("VPSTxId" => $sagepayVpsTxId,
                            "Result" => "Flagged as TEST transaction.")
                    );

                    continue;
                }

                //get transaction data from sagepay
                $response = $this->_transactionsApi->getTransactionDetails($sagepayVpsTxId);

                if (!empty($response) && isset($response->t3maction)) {

                    $trmaction = (string)$response->t3maction;
                    $t3mscore = (string)$response->t3mscore;


                    if ($trmaction == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_NORESULT) {
                        //still no result, do nothing
                    } else {
                        //process fraud action
                        //@toDo

                        $transaction->setSagepaysuiteFraudCheck(1);
                    }

                    /**
                     * save fraud information in the payment as the transaction
                     * additional info of the transactions doesn't seem to be working
                     */
                    $payment->setAdditionalInformation("t3maction", (string)$trmaction)
                        ->setAdditionalInformation("t3mscore", (string)$t3mscore)
                        ->save();

                    $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                        array("VPSTxId" => $sagepayVpsTxId,
                            "Result" => $trmaction)
                    );
                }

            } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
                $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                    array("VPSTxId" => $sagepayVpsTxId,
                        "Result" => $apiException->getUserMessage())
                );

            } catch (\Exception $e) {
                $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                    array("VPSTxId" => $sagepayVpsTxId,
                        "Result" => $e->getMessage())
                );
            }
        }
    }
}