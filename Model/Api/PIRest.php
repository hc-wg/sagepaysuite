<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
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

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface */
    private $piCaptureResultFactory;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethodInterface */
    private $paymentMethodResultFactory;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCardInterface */
    private $cardResultFactory;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeDInterface */
    private $threedStatusResultFactory;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultAmountFactory */
    private $amountResultFactory;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyResponseFactory */
    private $mskResponse;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyRequestFactory */
    private $mskRequest;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiThreeDSecureRequestFactory */
    private $threedRequest;

    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiRefundRequestFactory */
    private $refundRequest;

    /**
     * PIRest constructor.
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param ApiExceptionFactory $apiExceptionFactory
     * @param Logger $suiteLogger
     * @param \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory $piCaptureResultFactory
     * @param \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethodFactory $paymentMethodResultFactory
     * @param \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCardFactory $cardResultFactory
     * @param \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeDFactory $threedResultFactory
     * @param \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultAmountFactory $amountResultFactory
     * @param \Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyResponseFactory $mskResponse
     * @param \Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyRequestFactory $mskRequest
     * @param \Ebizmarts\SagePaySuite\Api\SagePayData\PiThreeDSecureRequestFactory $threeDRequest
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultFactory $piCaptureResultFactory,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethodFactory $paymentMethodResultFactory,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCardFactory $cardResultFactory,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeDFactory $threedResultFactory,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultAmountFactory $amountResultFactory,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyResponseFactory $mskResponse,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyRequestFactory $mskRequest,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiThreeDSecureRequestFactory $threeDRequest,
        \Ebizmarts\SagePaySuite\Api\SagePayData\PiRefundRequestFactory $refundRequest
    ) {

        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_curlFactory               = $curlFactory;
        $this->_apiExceptionFactory       = $apiExceptionFactory;
        $this->_suiteLogger               = $suiteLogger;
        $this->piCaptureResultFactory     = $piCaptureResultFactory;
        $this->paymentMethodResultFactory = $paymentMethodResultFactory;
        $this->cardResultFactory          = $cardResultFactory;
        $this->threedStatusResultFactory  = $threedResultFactory;
        $this->amountResultFactory        = $amountResultFactory;
        $this->mskResponse                = $mskResponse;
        $this->mskRequest                 = $mskRequest;
        $this->threedRequest              = $threeDRequest;
        $this->refundRequest              = $refundRequest;
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
     * @return \Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyResponseInterface
     * @throws
     */
    public function generateMerchantKey()
    {
        /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyRequest $request */
        $request = $this->mskRequest->create();
        $request->setVendorName($this->_config->getVendorname());

        $jsonBody = json_encode($request->__toArray());

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $jsonBody, [__METHOD__, __LINE__]);

        $url = $this->_getServiceUrl(self::ACTION_GENERATE_MERCHANT_KEY);

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $url, [__METHOD__, __LINE__]);

        $result = $this->_executePostRequest($url, $jsonBody);

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $result, [__METHOD__, __LINE__]);

        $resultData = $this->processResponse($result);

        /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiMerchantSessionKeyResponse $response */
        $response = $this->mskResponse->create();
        $response->setExpiry($resultData->expiry);
        $response->setMerchantSessionKey($resultData->merchantSessionKey);

        return $response;
    }

    /**
     * Make capture payment request
     *
     * @param $paymentRequest
     * @return \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface
     * @throws \Ebizmarts\SagePaySuite\Model\Api\ApiException
     */
    public function capture($paymentRequest)
    {
        //log request
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $paymentRequest, [__METHOD__, __LINE__]);

        $jsonRequest = json_encode($paymentRequest);
        $result = $this->_executePostRequest($this->_getServiceUrl(self::ACTION_TRANSACTIONS), $jsonRequest);

        //log result
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $result, [__METHOD__, __LINE__]);

        $captureResult = $this->processResponse($result);

        return $this->getTransactionDetailsObject($captureResult);
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
        /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiThreeDSecureRequest $request */
        $request = $this->threedRequest->create();
        $request->setParEs($paRes);

        $jsonBody = json_encode($request->__toArray());

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $jsonBody, [__METHOD__, __LINE__]);

        $result = $this->_executePostRequest($this->_getServiceUrl(self::ACTION_SUBMIT_3D, $vpsTxId), $jsonBody);

        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $result, [__METHOD__, __LINE__]);

        $resultData = $this->processResponse($result);

        /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD $response */
        $response = $this->threedStatusResultFactory->create();
        $response->setStatus($resultData->status);

        return $response;
    }

    /**
     * @param $vendorTxCode
     * @param $refTransactionId
     * @param $amount
     * @param $currency
     * @param $description
     * @return \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface
     */
    public function refund($vendorTxCode, $refTransactionId, $amount, $currency, $description)
    {
        /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiRefundRequest $refundRequest */
        $refundRequest = $this->refundRequest->create();
        $refundRequest->setTransactionType();
        $refundRequest->setVendorTxCode($vendorTxCode);
        $refundRequest->setReferenceTransactionId($refTransactionId);
        $refundRequest->setAmount($amount);
        $refundRequest->setDescription($description);

        //log request
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $refundRequest->__toArray(), [__METHOD__, __LINE__]);

        $jsonRequest = json_encode($refundRequest->__toArray());
        $result      = $this->_executePostRequest($this->_getServiceUrl(self::ACTION_TRANSACTIONS), $jsonRequest);

        //log result
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $result, [__METHOD__, __LINE__]);

        return $this->getTransactionDetailsObject($this->processResponse($result));
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
     * @return \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface
     * @throws \Ebizmarts\SagePaySuite\Model\Api\ApiException
     */
    public function transactionDetails($vpsTxId)
    {
        $result = $this->_executeRequest($this->_getServiceUrl(self::ACTION_TRANSACTION_DETAILS, $vpsTxId));

        if ($result["status"] == 200) {
            return $this->getTransactionDetailsObject($result["data"]);
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
            //authentication required (3D secure)
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

    /**
     * @param \stdClass $captureResult
     * @return \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface
     */
    private function getTransactionDetailsObject(\stdClass $captureResult)
    {
        /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface $transaction */
        $transaction = $this->piCaptureResultFactory->create();
        $transaction->setStatusCode($captureResult->statusCode);
        $transaction->setStatusDetail($captureResult->statusDetail);
        $transaction->setTransactionId($captureResult->transactionId);
        $transaction->setStatus($captureResult->status);

        if ($captureResult->status == '3DAuth') {
            $transaction->setAcsUrl($captureResult->acsUrl);
            $transaction->setParEq($captureResult->paReq);
        } else {
            $transaction->setTransactionType($captureResult->transactionType);
            $transaction->setRetrievalReference($captureResult->retrievalReference);
            $transaction->setBankAuthCode($captureResult->bankAuthorisationCode);

            if (isset($captureResult->currency)) {
                $transaction->setCurrency($captureResult->currency);
            }

            if (isset($captureResult->bankResponseCode)) {
                $transaction->setBankResponseCode($captureResult->bankResponseCode);
            }

            /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCard $card */
            $card = $this->cardResultFactory->create();

            if (isset($captureResult->paymentMethod->card->cardIdentifier)) {
                $card->setCardIdentifier($captureResult->paymentMethod->card->cardIdentifier);
            }

            if (isset($captureResult->paymentMethod->card->reusable)) {
                $card->setIsReusable($captureResult->paymentMethod->card->reusable);
            }

            $card->setCardType($captureResult->paymentMethod->card->cardType);
            $card->setLastFourDigits($captureResult->paymentMethod->card->lastFourDigits);
            $card->setExpiryDate($captureResult->paymentMethod->card->expiryDate);

            /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultPaymentMethod $paymentMethod */
            $paymentMethod = $this->paymentMethodResultFactory->create();
            $paymentMethod->setCard($card);

            $transaction->setPaymentMethod($paymentMethod);

            if (isset($captureResult->{'3DSecure'})) {
                /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultThreeD $threedstatus */
                $threedstatus = $this->threedStatusResultFactory->create();
                $threedstatus->setStatus($captureResult->{'3DSecure'}->status);
                $transaction->setThreeDSecure($threedstatus);
            }

            if (isset($captureResult->amount)) {
                /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultAmountInterface $amount */
                $amount = $this->amountResultFactory->create();
                $amount->setSaleAmount($captureResult->amount->saleAmount);
                $amount->setTotalAmount($captureResult->amount->totalAmount);
                $amount->setSurchargeAmount($captureResult->amount->surchargeAmount);
                $transaction->setAmount($amount);
            }
        }

        return $transaction;
    }
}
