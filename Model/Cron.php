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
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_mailTransportBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param Api\Reporting $reportingApi
     * @param Logger\Logger $suiteLogger
     * @param \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Config $config
     * @param \Magento\Framework\Mail\Template\TransportBuilder $mailTransportBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param Order\Payment\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Ebizmarts\SagePaySuite\Model\Api\Reporting $reportingApi,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Framework\Mail\Template\TransportBuilder $mailTransportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
    )
    {
        $this->_resource = $resource;
        $this->_reportingApi = $reportingApi;
        $this->_suiteLogger = $suiteLogger;
        $this->_orderPaymentRepository = $orderPaymentRepository;
        $this->_objectManager = $objectManager;
        $this->_config = $config;
        $this->_mailTransportBuilder = $mailTransportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->_orderFactory = $orderFactory;
        $this->_transactionFactory = $transactionFactory;
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
        //$this->_suiteLogger->SageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_CRON, "Running Fraud checks...");

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

            $sagepayVpsTxId = $transaction->getTxnId();
            $logData = array("VPSTxId" => $sagepayVpsTxId);

            try {

                $payment = $this->_orderPaymentRepository->get($transaction->getPaymentId());
                if (is_null($payment)) {
                    throw new LocalizedException(__('Payment not found for this transaction'));
                }

                //flag test transactions (no actions taken with test orders)
                if ($payment->getAdditionalInformation("mode") &&
                    $payment->getAdditionalInformation("mode") == \Ebizmarts\SagePaySuite\Model\Config::MODE_TEST
                ) {
                    /**
                     *  TEST TRANSACTION
                     */
                    $transaction->setSagepaysuiteFraudCheck(1)->save();
                    $logData["Action"] = "Marked as TEST";

                } else {

                    /**
                     * LIVE TRANSACTION
                     */

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
                            $fraudscreenrecommendation != \Ebizmarts\SagePaySuite\Model\Config::ReDSTATUS_NOTCHECKED
                        ) {
                            //mark payment as fraud
                            if ($fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT ||
                                $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::ReDSTATUS_DENY
                            ) {
                                $payment->setIsFraudDetected(true);
                                $payment->getOrder()->setStatus(Order::STATUS_FRAUD);
                                $payment->save();
                            }

                            //mark as checked
                            $transaction->setSagepaysuiteFraudCheck(1);
                            $transaction->save();

                            /**
                             * process fraud actions
                             */

                            //auto-invoice authorized order for full amount if ACCEPT or OK
                            if ((bool)$this->_config->getAutoInvoiceFraudPassed() == true &&
                                $transaction->getTxnType() == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH &&
                                (bool)$transaction->getIsTransactionClosed() == false &&
                                ($fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::ReDSTATUS_ACCEPT ||
                                    $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_OK)
                            ) {
                                //create invoice
                                $invoice = $payment->getOrder()->prepareInvoice();
                                $invoice->register();
                                $invoice->capture();
                                $invoice->save();
                                $payment->getOrder()->addRelatedObject($invoice);
                                $payment->save();
                                $logData["Action"] = "Captured online, invoice #" . $invoice->getId() . " generated.";
                            }

                            //send notification email
//                            if ((string)$this->_config->getNotifyFraudResult() != 'disabled') {
//                                if (((string)$this->_config->getNotifyFraudResult() == "medium_risk" &&
//                                        ($fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::ReDSTATUS_DENY ||
//                                            $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::ReDSTATUS_CHALLENGE ||
//                                            $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT ||
//                                            $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_HOLD))
//                                    ||
//                                    ((string)$this->_config->getNotifyFraudResult() == "high_risk" &&
//                                        ($fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::ReDSTATUS_DENY ||
//                                            $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT))
//                                ) {
//                                    $template = "sagepaysuite_fraud_notification";
//                                    $transport = $this->_mailTransportBuilder->setTemplateIdentifier($template)
//                                        ->addTo($this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
//                                        ->setFrom($this->scopeConfig->getValue("contact/email/sender_email_identity", \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
//                                        ->setTemplateOptions(['area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
//                                            'store' => Store::DEFAULT_STORE_ID])
//                                        ->setTemplateVars([
//                                            'transaction_id' => $transaction->getTransactionId(),
//                                            'order_id' => $payment->getOrder()->getIncrementId(),
////                                            'order_url' => $this->_urlBuilder->getUrl('sales/order/view/',array('order_id'=>$payment->getOrder()->getEntityId())),
//                                            'vps_tx_id' => $sagepayVpsTxId,
//                                            'fraud_id' => $fraudid,
//                                            'recommendation' => $fraudscreenrecommendation,
//                                            'detail' => $fraudcodedetail,
//                                            'provider' => $fraudprovidername,
//                                            'rules' => $rules
//                                        ])
//                                        ->getTransport();
//                                    $transport->sendMessage();
//
//                                    $logData["Notification"] = "Email sent to " . $this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
//                                }
//                            }
                            /**
                             * END process fraud actions
                             */

                            /**
                             * save fraud information in the payment as the transaction
                             * additional info of the transactions does not seem to be working
                             */
                            $payment->setAdditionalInformation("fraudscreenrecommendation", (string)$fraudscreenrecommendation);
                            $payment->setAdditionalInformation("fraudid", (string)$fraudid);
                            $payment->setAdditionalInformation("fraudcode", (string)$fraudcode);
                            $payment->setAdditionalInformation("fraudcodedetail", (string)$fraudcodedetail);
                            $payment->setAdditionalInformation("fraudprovidername", (string)$fraudprovidername);
                            $payment->setAdditionalInformation("fraudrules", (string)$rules);
                            $payment->save();

                            $logData["fraudscreenrecommendation"] = $fraudscreenrecommendation;
                            $logData["fraudid"] = $fraudid;
                            $logData["fraudcode"] = $fraudcode;
                            $logData["fraudcodedetail"] = $fraudcodedetail;
                            $logData["fraudprovidername"] = $fraudprovidername;
                            $logData["fraudrules"] = $rules;

                        } else {

                            //save the "not checked" or "no result" status
                            $payment->setAdditionalInformation("fraudscreenrecommendation", (string)$fraudscreenrecommendation);
                            $payment->save();

                            $logData["fraudscreenrecommendation"] = $fraudscreenrecommendation;
                        }
                    } else {
                        $logData["ERROR"] = "Invalid Response: " . (!empty($response) && isset($response->errorcode) ? $response->errorcode : "INVALID");
                    }
                }
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