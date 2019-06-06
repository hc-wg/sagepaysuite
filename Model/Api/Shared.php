<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Api;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

/**
 * Sage Pay Shared API
 */
class Shared
{
    const DEFERRED_AWAITING_RELEASE = 14;
    const SUCCESSFULLY_AUTHORISED   = 16;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory
     */
    private $_apiExceptionFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Reporting
     */
    private $_reportingApi;

    /** @var \Ebizmarts\SagePaySuite\Model\Api\HttpTextFactory  */
    private $httpTextFactory;

    private $requestHelper;

    /**
     * Shared constructor.
     * @param HttpTextFactory $httpTextFactory
     * @param ApiExceptionFactory $apiExceptionFactory
     * @param Config $config
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     * @param Reporting $reportingApi
     * @param Logger $suiteLogger
     */
    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Api\HttpTextFactory $httpTextFactory,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Api\Reporting $reportingApi,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper
    ) {
        $this->_config              = $config;
        $this->_apiExceptionFactory = $apiExceptionFactory;
        $this->_suiteLogger         = $suiteLogger;
        $this->_suiteHelper         = $suiteHelper;
        $this->_reportingApi        = $reportingApi;
        $this->httpTextFactory      = $httpTextFactory;
        $this->requestHelper        = $requestHelper;
    }

    public function voidTransaction($vpstxid, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $transaction = $this->_reportingApi->getTransactionDetails($vpstxid, $order->getStoreId());

        $data['VPSProtocol']  = $this->_config->getVPSProtocol();
        $data['TxType']       = Config::ACTION_VOID;
        $data['Vendor']       = $this->_config->getVendorname();
        $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode();
        $data['VPSTxId']      = (string)$transaction->vpstxid;
        $data['SecurityKey']  = (string)$transaction->securitykey;
        $data['TxAuthNo']     = (string)$transaction->vpsauthcode;

        return $this->_executeRequest(Config::ACTION_VOID, $data);
    }

    public function refundTransaction($vpstxid, $amount, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $transaction = $this->_reportingApi->getTransactionDetails($vpstxid, $order->getStoreId());

        $data['VPSProtocol']         = $this->_config->getVPSProtocol();
        $data['TxType']              = Config::ACTION_REFUND;
        $data['Vendor']              = $this->_config->getVendorname();
        $data['VendorTxCode']        = $this->_suiteHelper->generateVendorTxCode($order->getIncrementId(), Config::ACTION_REFUND);
        $data['Amount']              = number_format($amount, 2, '.', '');
        $data['Currency']            = (string)$transaction->currency;
        $data['Description']         = "Refund issued from magento.";
        $data['RelatedVPSTxId']      = (string)$transaction->vpstxid;
        $data['RelatedVendorTxCode'] = (string)$transaction->vendortxcode;
        $data['RelatedSecurityKey']  = (string)$transaction->securitykey;
        $data['RelatedTxAuthNo']     = (string)$transaction->vpsauthcode;

        return $this->_executeRequest(Config::ACTION_REFUND, $data);
    }

    public function abortDeferredTransaction($vpstxid, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $transaction = $this->reportingApi->getTransactionDetails($vpstxid, $order->getStoreId());

        $data['VPSProtocol']  = $this->_config->getVPSProtocol();
        $data['TxType']       = Config::ACTION_ABORT;
        $data['ReferrerID']   = $this->requestHelper->getReferrerId();
        $data['Vendor']       = $this->_config->getVendorname();
        $data['VendorTxCode'] = (string)$transaction->vendortxcode;
        $data['VPSTxId']      = (string)$transaction->vpstxid;
        $data['SecurityKey']  = (string)$transaction->securitykey;
        $data['TxAuthNo']     = (string)$transaction->vpsauthcode;

        return $this->executeRequest(Config::ACTION_ABORT, $data);
    }

    public function captureDeferredTransaction($vpsTxId, $amount, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $vpsTxId = $this->_suiteHelper->clearTransactionId($vpsTxId);

        $transaction = $this->_reportingApi->getTransactionDetails($vpsTxId, $order->getStoreId());
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $transaction, [__METHOD__, __LINE__]);

        $result = null;

        $txStateId = (int)$transaction->txstateid;
        if ($txStateId == self::DEFERRED_AWAITING_RELEASE) {
            $result = $this->releaseTransaction($vpsTxId, $amount, $order);
        } else {
            if($txStateId == self::SUCCESSFULLY_AUTHORISED) {
                $data = [];
                $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode("", Config::ACTION_REPEAT);
                $data['Description']  = "REPEAT deferred transaction from Magento.";
                $data['ReferrerID']   = $this->requestHelper->getReferrerId();
                $data['Currency']     = (string)$transaction->currency;
                $data['Amount']       = $amount;
                $result = $this->repeatTransaction($vpsTxId, $data, $order, Config::ACTION_REPEAT);
            }
        }

        return $result;
    }

    public function releaseTransaction($vpstxid, $amount, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $transaction = $this->_reportingApi->getTransactionDetails($vpstxid, $order->getStoreId());

        $data['VPSProtocol']   = $this->_config->getVPSProtocol();
        $data['TxType']        = Config::ACTION_RELEASE;
        $data['Vendor']        = $this->_config->getVendorname();
        $data['VendorTxCode']  = (string)$transaction->vendortxcode;
        $data['VPSTxId']       = (string)$transaction->vpstxid;
        $data['SecurityKey']   = (string)$transaction->securitykey;
        $data['TxAuthNo']      = (string)$transaction->vpsauthcode;
        $data['ReleaseAmount'] = number_format($amount, 2, '.', '');

        return $this->_executeRequest(Config::ACTION_RELEASE, $data);
    }

    public function authorizeTransaction($vpstxid, $amount, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $transaction = $this->_reportingApi->getTransactionDetails($vpstxid, $order->getStoreId());

        $data['VPSProtocol']         = $this->_config->getVPSProtocol();
        $data['TxType']              = \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHORISE;
        $data['Vendor']              = $this->_config->getVendorname();
        $data['VendorTxCode']        = $this->_suiteHelper->generateVendorTxCode($order->getIncrementId(), Config::ACTION_AUTHORISE);
        $data['Amount']              = number_format($amount, 2, '.', '');
        $data['Description']         = "Authorise transaction from Magento";
        $data['RelatedVPSTxId']      = (string)$transaction->vpstxid;
        $data['RelatedVendorTxCode'] = (string)$transaction->vendortxcode;
        $data['RelatedSecurityKey']  = (string)$transaction->securitykey;
        $data['RelatedTxAuthNo']     = (string)$transaction->vpsauthcode;

        return $this->_executeRequest(Config::ACTION_AUTHORISE, $data);
    }

    public function repeatTransaction($vpstxid, $quote_data, \Magento\Sales\Api\Data\OrderInterface $order, $paymentAction = Config::ACTION_REPEAT)
    {
        $transaction = $this->_reportingApi->getTransactionDetails($vpstxid, $order->getStoreId());

        $data['VPSProtocol'] = $this->_config->getVPSProtocol();
        $data['TxType']      = $paymentAction;
        $data['Vendor']      = $this->_config->getVendorname();

        //populate quote data
        $data = array_merge($data, $quote_data);

        $data['Description']         = "Repeat transaction from Magento";
        $data['RelatedVPSTxId']      = (string)$transaction->vpstxid;
        $data['RelatedVendorTxCode'] = (string)$transaction->vendortxcode;
        $data['RelatedSecurityKey']  = (string)$transaction->securitykey;
        $data['RelatedTxAuthNo']     = (string)$transaction->vpsauthcode;

        return $this->_executeRequest($paymentAction, $data);
    }

    /**
     * Executes curl request
     *
     * @param $action
     * @param $data
     * @return array
     */
    private function _executeRequest($action, $data)
    {
        $url = $this->_config->getServiceUrl($action);

        /** @var \Ebizmarts\SagePaySuite\Model\Api\HttpText $rest */
        $rest = $this->httpTextFactory->create();
        $body = $rest->arrayToQueryParams($data);
        $rest->setUrl($url);
        $response = $rest->executePost($body);

        //parse response
        if ($response->getStatus() == 200) {
            $responseData = $rest->rawResponseToArray();
        } else {
            $responseData = [];
            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, "INVALID RESPONSE FROM SAGE PAY: " . $response->getResponseCode(), [__METHOD__, __LINE__]);
        }

        $response = [
            "status" => $response->getStatus(),
            "data"   => $responseData
        ];

        $apiResponse = $this->_handleApiErrors($response);

        //log response
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $apiResponse, [__METHOD__, __LINE__]);

        return $apiResponse;
    }

    /**
     * @param $response
     * @return array
     * @throws \Ebizmarts\SagePaySuite\Model\Api\ApiException
     */
    private function _handleApiErrors($response)
    {
        $exceptionPhrase = "Invalid response from Sage Pay API.";
        $exceptionCode = 0;
        $validResponse = false;

        if (!empty($response) && array_key_exists("data", $response)) {
            if (array_key_exists("Status", $response["data"]) && $response["data"]["Status"] == 'OK') {
                //this is a successfull response
                return $response;
            } else {
                //there was an error
                if (array_key_exists("StatusDetail", $response["data"])) {
                    $detail = explode(":", $response["data"]["StatusDetail"]);
                    $exceptionCode = trim($detail[0]);
                    $exceptionPhrase = trim($detail[1]);
                    $validResponse = true;
                }
            }
        }

        if (!$validResponse) {
            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $response, [__METHOD__, __LINE__]);
        }

        $exception = $this->_apiExceptionFactory->create([
            'phrase' => __($exceptionPhrase),
            'code' => $exceptionCode
        ]);

        throw $exception;
    }
}
