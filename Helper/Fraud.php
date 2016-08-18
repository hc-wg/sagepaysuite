<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Helper;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Store\Model\Store;
use Magento\Sales\Model\Order;

class Fraud extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_mailTransportBuilder;

    /**
     * \Ebizmarts\SagePaySuite\Model\Api\Reporting
     */
    protected $_reportingApi;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Framework\Mail\Template\TransportBuilder $mailTransportBuilder,
        \Ebizmarts\SagePaySuite\Model\Api\Reporting $reportingApi
    ) {
    
        parent::__construct($context);
        $this->_suiteLogger = $suiteLogger;
        $this->_config = $config;
        $this->_mailTransportBuilder = $mailTransportBuilder;
        $this->_reportingApi = $reportingApi;
    }

    /**
     * @param $transaction
     * @param $payment
     * @return array
     */
    public function processFraudInformation($transaction, $payment)
    {
        $sagepayVpsTxId = $transaction->getTxnId();

        $logData = ["VPSTxId" => $sagepayVpsTxId];

        //flag test transactions (no actions taken with test orders)
        if ($payment->getAdditionalInformation("mode") &&
            $payment->getAdditionalInformation("mode") == \Ebizmarts\SagePaySuite\Model\Config::MODE_TEST
        ) {
            /**
             *  TEST TRANSACTION
             */

            $transaction->setSagepaysuiteFraudCheck(1);
            $transaction->save();
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
                    $fraudscreenrecommendation != \Ebizmarts\SagePaySuite\Model\Config::REDSTATUS_NOTCHECKED
                ) {
                    //mark payment as fraud
                    if ($fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT ||
                        $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::REDSTATUS_DENY
                    ) {
                        $payment->setIsFraudDetected(true);
                        $payment->getOrder()->setStatus(Order::STATUS_FRAUD);
                        $payment->save();
                        $logData["Action"] = "Marked as FRAUD.";
                    }

                    //mark as checked
                    $transaction->setSagepaysuiteFraudCheck(1);
                    $transaction->save();

                    /**
                     * process fraud actions
                     */

                    //auto-invoice
                    $autoInvoiceActioned = $this->_processAutoInvoice($transaction, $payment, $fraudscreenrecommendation);
                    if (!empty($autoInvoiceActioned)) {
                        $logData["Action"] = $autoInvoiceActioned;
                    }

                    //notification
//                    $notificationActioned = $this->_notification($transaction, $payment,
//                        $fraudscreenrecommendation,
//                        $fraudid,
//                        $fraudcodedetail,
//                        $fraudprovidername,
//                        $rules);
//                    if (!empty($notificationActioned)) {
//                        $logData["Notification"] = $notificationActioned;
//                    }

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

        return $logData;
    }

    protected function _processAutoInvoice(\Magento\Sales\Model\Order\Payment\Transaction $transaction, \Magento\Sales\Model\Order\Payment $payment, $fraudscreenrecommendation)
    {
        //auto-invoice authorized order for full amount if ACCEPT or OK
        if ((bool)$this->_config->getAutoInvoiceFraudPassed() == true &&
            $transaction->getTxnType() == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH &&
            (bool)$transaction->getIsTransactionClosed() == false &&
            ($fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::REDSTATUS_ACCEPT ||
                $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_OK)
        ) {
            //create invoice
            $invoice = $payment->getOrder();
            $invoice->prepareInvoice();
            $invoice->register();
            $invoice->capture();
            $invoice->save();
            $payment->getOrder()->addRelatedObject($invoice);
            $payment->save();
            return "Captured online, invoice #" . $invoice->getId() . " generated.";
        } else {
            return false;
        }
    }

    protected function _notification(
        \Magento\Sales\Model\Order\Payment\Transaction $transaction,
        \Magento\Sales\Model\Order\Payment $payment,
        $fraudscreenrecommendation,
        $fraudid,
        $fraudcodedetail,
        $fraudprovidername,
        $rules
    ) {
    
        if ((string)$this->_config->getNotifyFraudResult() != 'disabled') {
            if (((string)$this->_config->getNotifyFraudResult() == "medium_risk" &&
                    ($fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::REDSTATUS_DENY ||
                        $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::REDSTATUS_CHALLENGE ||
                        $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT ||
                        $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_HOLD))
                ||
                ((string)$this->_config->getNotifyFraudResult() == "high_risk" &&
                    ($fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::REDSTATUS_DENY ||
                        $fraudscreenrecommendation == \Ebizmarts\SagePaySuite\Model\Config::T3STATUS_REJECT))
            ) {
                $template = "sagepaysuite_fraud_notification";
                $transport = $this->_mailTransportBuilder->setTemplateIdentifier($template)
                    ->addTo($this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
                    ->setFrom($this->scopeConfig->getValue("contact/email/sender_email_identity", \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
                    ->setTemplateOptions(['area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                        'store' => Store::DEFAULT_STORE_ID])
                    ->setTemplateVars([
                        'transaction_id' => $transaction->getTransactionId(),
                        'order_id' => $payment->getOrder()->getIncrementId(),
//                        'order_url' => $this->_urlBuilder->getUrl('sales/order/view/', array('order_id' => $payment->getOrder()->getEntityId())),
                        'vps_tx_id' => $transaction->getTxnId(),
                        'fraud_id' => $fraudid,
                        'recommendation' => $fraudscreenrecommendation,
                        'detail' => $fraudcodedetail,
                        'provider' => $fraudprovidername,
                        'rules' => $rules
                    ])
                    ->getTransport();
                $transport->sendMessage();

                return "Email sent to " . $this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            }
        }

        return false;
    }
}
