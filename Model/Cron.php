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
     * \Ebizmarts\SagePaySuite\Model\Api\Reporting
     */
    protected $_reportingApi;

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
        \Ebizmarts\SagePaySuite\Model\Api\Reporting $reportingApi,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_resource = $resource;
        $this->_reportingApi = $reportingApi;
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
        $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON, "Running Fraud checks...");

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

            $sagepayVpsTxId = $transaction->getTxnId();

            try {

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
                $response = $this->_reportingApi->getFraudScreenDetail($sagepayVpsTxId);

                if (!empty($response) && isset($response->errorcode) && $response->errorcode == "0000") {

                    $fraudscreenrecommendation = (string)$response->fraudscreenrecommendation;
                    $fraudid = (string)$response->fraudid;
                    $fraudcode = (string)$response->fraudcode;
                    $fraudcodedetail = (string)$response->fraudcodedetail;
                    $fraudprovidername = (string)$response->fraudprovidername;
                    $rules = (string)$response->rules;

                    if ($fraudscreenrecommendation != \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_NORESULT &&
                        $fraudscreenrecommendation != \Ebizmarts\SagePaySuite\Model\Config::ReDSTATUS_NOTCHECKED)
                    {

                        //process fraud action
                        //@toDo

                        $transaction->setSagepaysuiteFraudCheck(1)->save();

                        /**
                         * save fraud information in the payment as the transaction
                         * additional info of the transactions doesn't seem to be working
                         */
                        $payment->setAdditionalInformation("fraudscreenrecommendation", (string)$fraudscreenrecommendation)
                            ->setAdditionalInformation("fraudid", (string)$fraudid)
                            ->setAdditionalInformation("fraudcode", (string)$fraudcode)
                            ->setAdditionalInformation("fraudcodedetail", (string)$fraudcodedetail)
                            ->setAdditionalInformation("fraudprovidername", (string)$fraudprovidername)
                            ->setAdditionalInformation("fraudrules", (string)$rules)
                            ->save();

                        $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                            array("VPSTxId" => $sagepayVpsTxId,
                                "fraudscreenrecommendation" => $fraudscreenrecommendation,
                                "fraudid" => $fraudid,
                                "fraudcode" => $fraudcode,
                                "fraudcodedetail" => $fraudcodedetail,
                                "fraudprovidername" => $fraudprovidername,
                                "fraudrules" => $rules
                            )
                        );
                    }else{

                        //save the "not checked" opr "no result" status
                        $payment->setAdditionalInformation("fraudscreenrecommendation", (string)$fraudscreenrecommendation)
                            ->save();

                        $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,
                            array("VPSTxId" => $sagepayVpsTxId,
                                "fraudscreenrecommendation" => $fraudscreenrecommendation
                            )
                        );
                    }
                }else{
                    $this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON,"ERROR");
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