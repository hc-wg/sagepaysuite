<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Api;


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
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Ebizmarts\SagePaySuite\Model\Api\ReportingApi $reportingApi
     * @param \Ebizmarts\SagePaySuite\Model\Api\SharedApi $sharedApi
     * @param \Magento\Sales\Model\Order\Payment\TransactionRepository $transactionRepo
     */
    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Api\ReportingApi $reportingApi,
        \Ebizmarts\SagePaySuite\Model\Api\SharedApi $sharedApi,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
    )
    {
        $this->_reportingApi = $reportingApi;
        $this->_sharedApi = $sharedApi;
        $this->_config = $config;
        $this->_suiteHelper = $suiteHelper;
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
        $order =

        $data['VPSProtocol'] = $this->_config->getVPSProtocol();
        $data['TxType'] = \Ebizmarts\SagePaySuite\Model\Config::ACTION_VOID;
        $data['Vendor'] = $this->_config->getVendorname();
        $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode();
        $data['VPSTxId'] = $transaction->vpstxid;
        $data['SecurityKey'] = $transaction->securitykey;
        $data['TxAuthNo'] = $transaction->vpsauthcode;

        $response = $this->_sharedApi->executeRequest(
            \Ebizmarts\SagePaySuite\Model\Config::ACTION_VOID,
            $data
        );

        return $this->_sharedApi->handleApiErrors($response);
    }

    public function refundTransaction($vpstxid, $amount, $order_id){

        $transaction = $this->getTransactionDetails($vpstxid);

        $data['VPSProtocol'] = $this->_config->getVPSProtocol();
        $data['TxType'] = \Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND;
        $data['Vendor'] = $this->_config->getVendorname();
        $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode($order_id,\Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND);
        $data['Amount'] = $amount;
        $data['Currency'] = $transaction->currency;
        $data['Description'] = "Refund issued from magento.";
        $data['RelatedVPSTxId'] = $transaction->vpstxid;
        $data['RelatedVendorTxCode'] = $transaction->vendortxcode;
        $data['RelatedSecurityKey'] = $transaction->securitykey;
        $data['RelatedTxAuthNo'] = $transaction->vpsauthcode;

        $response = $this->_sharedApi->executeRequest(
            \Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND,
            $data
        );

        return $this->_sharedApi->handleApiErrors($response);
    }



}
