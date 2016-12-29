<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Api;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

/**
 * Sage Pay PI REST API
 *
 * @see https://live.sagepay.com/documentation/
 */
class PIRest
{
    const ACTION_GENERATE_MERCHANT_KEY    = 'merchant-session-keys';
    const ACTION_TRANSACTIONS             = 'transactions';
    const ACTION_TRANSACTION_INSTRUCTIONS = 'transactions/%s/instructions';
    const ACTION_SUBMIT_3D                = '3d-secure';
    const ACTION_TRANSACTION_DETAILS      = 'transaction_details';

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     *
     */
    private $_curlFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory
     */
    private $_apiExceptionFactory;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param ApiExceptionFactory $apiExceptionFactory
     * @param Logger $suiteLogger
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        Logger $suiteLogger
    ) {

        $this->_config              = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_curlFactory         = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;
        $this->_suiteLogger         = $suiteLogger;
    }

    /**
     * Makes the Curl POST
     *
     * @param $url
     * @param $body
     * @return array
     */
    private function _executePostRequest($url, $body)
    {

        $curl = $this->_curlFactory->create();

        $curl->setConfig(
            [
                'timeout' => 120,
                'verifypeer' => false,
                'verifyhost' => 2,
                'userpwd' => $this->_config->getPIKey() . ":" . $this->_config->getPIPassword()
            ]
        );

        $curl->write(
            \Zend_Http_Client::POST,
            $url,
            '1.0',
            ['Content-type: application/json'],
            $body
        );
        $data = $curl->read();

        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
        $curl->close();

        $data = preg_split('/^\r?$/m', $data, 2);
        $data = json_decode(trim($data[1]));

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $data, [__METHOD__, __LINE__]);

        $response = [
            "status" => $response_status,
            "data" => $data
        ];

        return $response;
    }

    /**
     * Makes the Curl GET
     *
     * @param $url
     * @return array
     */
    private function _executeRequest($url)
    {

        $curl = $this->_curlFactory->create();

        $curl->setConfig(
            [
                'timeout' => 120,
                'verifypeer' => false,
                'verifyhost' => 2,
                'userpwd' => $this->_config->getPIKey() . ":" . $this->_config->getPIPassword()
            ]
        );

        $curl->write(
            \Zend_Http_Client::GET,
            $url,
            '1.0',
            ['Content-type: application/json']
        );
        $data = $curl->read();

        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
        $curl->close();

        $data = preg_split('/^\r?$/m', $data, 2);
        $data = json_decode(trim($data[1]));

        $response = [
            "status" => $response_status,
            "data" => $data
        ];

        return $response;
    }

    /**
     * Returns url for each enviroment according the configuration.
     * @param $action
     * @param null $vpsTxId
     * @return string
     */
    private function _getServiceUrl($action, $vpsTxId = null)
    {
        switch ($action) {
            case self::ACTION_TRANSACTION_DETAILS:
                $endpoint = "transactions/" . $vpsTxId;
                break;
            case self::ACTION_SUBMIT_3D:
                $endpoint = "transactions/" . $vpsTxId . "/" . $action;
                break;
            case self::ACTION_TRANSACTION_INSTRUCTIONS:
                $endpoint = sprintf(self::ACTION_TRANSACTION_INSTRUCTIONS, $vpsTxId);
                break;
            default:
                $endpoint = $action;
                break;
        }

        if ($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_LIVE . $endpoint;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . $endpoint;
        }
    }

    /**
     * Make POST request to ask for merchant key
     *
     * @return mixed
     * @throws
     */
    public function generateMerchantKey()
    {
        $jsonBody = json_encode(["vendorName" => $this->_config->getVendorname()]);

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $jsonBody, [__METHOD__, __LINE__]);

        $url = $this->_getServiceUrl(self::ACTION_GENERATE_MERCHANT_KEY);

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $url, [__METHOD__, __LINE__]);

        $result = $this->_executePostRequest($url, $jsonBody);

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $result, [__METHOD__, __LINE__]);

        $resultData = $this->processResponse($result);

        return $resultData->merchantSessionKey;
    }

    /**
     * Make capture payment request
     *
     * @param $payment_request
     * @return mixed
     * @throws
     */
    public function capture($payment_request)
    {
        //log request
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $payment_request, [__METHOD__, __LINE__]);

        $jsonRequest = json_encode($payment_request);
        $result = $this->_executePostRequest($this->_getServiceUrl(self::ACTION_TRANSACTIONS), $jsonRequest);

        //log result
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $result, [__METHOD__, __LINE__]);

        return $this->processResponse($result);
    }

    /**
     * Submit 3D result via POST
     *
     * @param $paRes
     * @param $vpsTxId
     * @return mixed
     * @throws
     */
    public function submit3D($paRes, $vpsTxId)
    {
        $jsonBody = json_encode(["paRes" => $paRes]);

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $jsonBody, [__METHOD__, __LINE__]);

        $result = $this->_executePostRequest($this->_getServiceUrl(self::ACTION_SUBMIT_3D, $vpsTxId), $jsonBody);

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $result, [__METHOD__, __LINE__]);

        return $this->processResponse($result);
    }

    /**
     * @param $vendorTxCode
     * @param $refTransactionId
     * @param $amount
     * @param $currency
     * @param $description
     * @return mixed
     */
    public function refund($vendorTxCode, $refTransactionId, $amount, $currency, $description)
    {
        $requestData = [
            'transactionType'        => 'Refund',
            'vendorTxCode'           => $vendorTxCode,
            'referenceTransactionId' => $refTransactionId,
            'amount'                 => $amount,
            'currency'               => $currency,
            'description'            => $description
        ];

        //log request
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $requestData, [__METHOD__, __LINE__]);

        $jsonRequest = json_encode($requestData);
        $result = $this->_executePostRequest($this->_getServiceUrl(self::ACTION_TRANSACTIONS), $jsonRequest);

        //log result
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $result, [__METHOD__, __LINE__]);

        return $this->processResponse($result);
    }

    /**
     * @param $transactionId
     * @return mixed
     */
    public function void($transactionId)
    {
        $requestData = ['instructionType' => 'void'];

        //log request
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $requestData, [__METHOD__, __LINE__]);

        $jsonRequest = json_encode($requestData);
        $result = $this->_executePostRequest(
            $this->_getServiceUrl(self::ACTION_TRANSACTION_INSTRUCTIONS, $transactionId), $jsonRequest
        );

        //log result
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $result, [__METHOD__, __LINE__]);

        return $this->processResponse($result);
    }

    /**
     * GET transaction details
     *
     * @param $vpsTxId
     * @return mixed
     * @throws
     */
    public function transactionDetails($vpsTxId)
    {

        $result = $this->_executeRequest($this->_getServiceUrl(self::ACTION_TRANSACTION_DETAILS, $vpsTxId));

        if ($result["status"] == 200) {
            return $result["data"];
        } else {
            $error_code = $result["data"]->code;
            $error_msg = $result["data"]->description;

            $exception = $this->_apiExceptionFactory->create([
                'phrase' => __($error_msg),
                'code' => $error_code
            ]);

            throw $exception;
        }
    }

    /**
     * @param array $result
     * @return mixed
     */
    private function processResponse($result)
    {
        if ($result["status"] == 201) {
            //success
            return $result["data"];
        } elseif ($result["status"] == 202) {
            //authentication required
            return $result["data"];
        } else {
            $errorCode = 0;
            $errorMessage  = "Unable to capture Sage Pay transaction";

            $errors = $result["data"];
            if (isset($errors->errors) && count($errors->errors) > 0) {
                $errors = $errors->errors[0];
            }

            if (isset($errors->code)) {
                $errorCode = $errors->code;
            }
            if (isset($errors->description)) {
                $errorMessage = $errors->description;
            }
            if (isset($errors->property)) {
                $errorMessage .= ': ' . $errors->property;
            }

            if (isset($errors->statusDetail)) {
                $errorMessage = $errors->statusDetail;
            }

            $exception = $this->_apiExceptionFactory->create(['phrase' => __($errorMessage), 'code' => $errorCode]);

            throw $exception;
        }
    }
}
