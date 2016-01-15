<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Api;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Transaction
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ReportingApi
     */
    private $_reportingApi;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\SharedApi
     */
    private $_sharedApi;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $_suiteHelper;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Ebizmarts\SagePaySuite\Model\Api\ReportingApi $reportingApi
     * @param \Ebizmarts\SagePaySuite\Model\Api\SharedApi $sharedApi
     * @param \Magento\Sales\Model\Order\Payment\TransactionRepository $transactionRepo
     */
    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Api\ReportingApi $reportingApi,
        \Ebizmarts\SagePaySuite\Model\Api\SharedApi $sharedApi,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        Logger $suiteLogger
    )
    {
        $this->_reportingApi = $reportingApi;
        $this->_sharedApi = $sharedApi;
        $this->_config = $config;
        $this->_suiteHelper = $suiteHelper;
        $this->_suiteLogger = $suiteLogger;
    }

    public function getTransactionDetails($vpstxid) {

        $params = '<vpstxid>' . $vpstxid . '</vpstxid>';
        $xml          = $this->_reportingApi->createXml('getTransactionDetail', $params);
        $api_response = $this->_reportingApi->executeRequest($xml);
        return $this->_reportingApi->handleApiErrors($api_response);
    }

    /**
     * @param String $vpstxid
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    public function voidTransaction($vpstxid){

        $transaction = $this->getTransactionDetails($vpstxid);

        $data['VPSProtocol'] = $this->_config->getVPSProtocol();
        $data['TxType'] = \Ebizmarts\SagePaySuite\Model\Config::ACTION_VOID;
        $data['Vendor'] = $this->_config->getVendorname();
        $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode();
        $data['VPSTxId'] = (string)$transaction->vpstxid;
        $data['SecurityKey'] = (string)$transaction->securitykey;
        $data['TxAuthNo'] = (string)$transaction->vpsauthcode;

        //log request
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $data);

        $response = $this->_sharedApi->executeRequest(
            \Ebizmarts\SagePaySuite\Model\Config::ACTION_VOID,
            $data
        );

        $api_response = $this->_sharedApi->handleApiErrors($response);

        //log response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $api_response);

        return $api_response;
    }

    public function refundTransaction($vpstxid, $amount, $order_id){

        $transaction = $this->getTransactionDetails($vpstxid);

        $data['VPSProtocol'] = $this->_config->getVPSProtocol();
        $data['TxType'] = \Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND;
        $data['Vendor'] = $this->_config->getVendorname();
        $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode($order_id,\Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND);
        $data['Amount'] = number_format($amount, 2, '.', '');
        $data['Currency'] = (string)$transaction->currency;
        $data['Description'] = "Refund issued from magento.";
        $data['RelatedVPSTxId'] = (string)$transaction->vpstxid;
        $data['RelatedVendorTxCode'] = (string)$transaction->vendortxcode;
        $data['RelatedSecurityKey'] = (string)$transaction->securitykey;
        $data['RelatedTxAuthNo'] = (string)$transaction->vpsauthcode;

        //log request
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $data);

        $response = $this->_sharedApi->executeRequest(
            \Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND,
            $data
        );

        $api_response = $this->_sharedApi->handleApiErrors($response);

        //log response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $api_response);

        return $api_response;
    }

    public function releaseTransaction($vpstxid,$amount)
    {
        $transaction = $this->getTransactionDetails($vpstxid);

        $data['VPSProtocol'] = $this->_config->getVPSProtocol();
        $data['TxType'] = \Ebizmarts\SagePaySuite\Model\Config::ACTION_RELEASE;
        $data['Vendor'] = $this->_config->getVendorname();
        $data['VendorTxCode'] = (string)$transaction->vendortxcode;
        $data['VPSTxId'] = (string)$transaction->vpstxid;
        $data['SecurityKey'] = (string)$transaction->securitykey;
        $data['TxAuthNo'] = (string)$transaction->vpsauthcode;
        $data['ReleaseAmount'] = number_format($amount, 2, '.', '');

        //log request
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $data);

        $response = $this->_sharedApi->executeRequest(
            \Ebizmarts\SagePaySuite\Model\Config::ACTION_RELEASE,
            $data
        );

        $api_response = $this->_sharedApi->handleApiErrors($response);

        //log response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $api_response);

        return $api_response;
    }

    public function authorizeTransaction($vpstxid,$amount,$order_id)
    {
        $transaction = $this->getTransactionDetails($vpstxid);

        $data['VPSProtocol'] = $this->_config->getVPSProtocol();
        $data['TxType'] = \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHORISE;
        $data['Vendor'] = $this->_config->getVendorname();
        $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode($order_id,\Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHORISE);
        $data['Amount'] = number_format($amount, 2, '.', '');
        $data['Description'] = "Authorize transaction from Magento";
        $data['RelatedVPSTxId'] = (string)$transaction->vpstxid;
        $data['RelatedVendorTxCode'] = (string)$transaction->vendortxcode;
        $data['RelatedSecurityKey'] = (string)$transaction->securitykey;
        $data['RelatedTxAuthNo'] = (string)$transaction->vpsauthcode;

        //log request
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $data);

        $response = $this->_sharedApi->executeRequest(
            \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHORISE,
            $data
        );

        $api_response = $this->_sharedApi->handleApiErrors($response);

        //log response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $api_response);

        return $api_response;
    }
}
