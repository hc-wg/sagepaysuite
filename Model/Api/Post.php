<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Api;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

/**
 * Sage Pay Reporting API parent class
 */
class Post
{

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     *
     */
    private $_curlFactory;

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
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    private $suiteHelper;

    /**
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param ApiExceptionFactory $apiExceptionFactory
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Request $suiteHelper
    ) {

        $this->_config              = $config;
        $this->_curlFactory         = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;
        $this->_suiteLogger         = $suiteLogger;
        $this->suiteHelper          = $suiteHelper;
    }

    private function _handleApiErrors($response, $expectedStatus, $defaultErrorMessage)
    {
        $success = false;

        if (!empty($response) &&
            $response["status"] == 200 &&
            array_key_exists("data", $response) &&
            array_key_exists("Status", $response["data"])
        ) {
            $expectedStatusCnt = count($expectedStatus);
            //check against all possible success response statuses
            for ($i = 0; $i < $expectedStatusCnt; $i++) {
                if ($response["data"]["Status"] == $expectedStatus[$i]) {
                    $success = true;
                }
            }
        }

        if ($success == true) {
            return $response;
        } else {
            //there was an error
            $exceptionPhrase = $defaultErrorMessage;
            $exceptionCode = 0;

            if (!empty($response) &&
                array_key_exists("data", $response) &&
                array_key_exists("StatusDetail", $response["data"])
            ) {
                $detail = explode(":", $response["data"]["StatusDetail"]);

                if (count($detail) == 2) {
                    $exceptionCode = trim($detail[0]);
                    $exceptionPhrase = trim($detail[1]);
                } else {
                    $exceptionPhrase = trim($detail[0]);
                }
            }

            $exception = $this->_apiExceptionFactory->create([
                'phrase' => __($exceptionPhrase),
                'code' => $exceptionCode
            ]);

            throw $exception;
        }
    }

    /**
     * @param $postData
     * @param $url
     * @param array $expectedStatus
     * @param string $errorMessage
     * @return mixed
     * @throws
     */
    public function sendPost($postData, $url, $expectedStatus = [], $errorMessage = "Invalid response from Sage Pay")
    {

        $curl = $this->_curlFactory->create();

        $post_data_string = $this->arrayToQueryParams($postData);

        //log request
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $postData, [__METHOD__, __LINE__]);

        $curl->setConfig(
            [
                'timeout' => 120,
                'verifypeer' => false,
                'verifyhost' => 2
            ]
        );

        $curl->write(
            \Zend_Http_Client::POST,
            $url,
            '1.0',
            [],
            $post_data_string
        );
        $data = $curl->read();

        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
        $curl->close();

        //log response
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $data, [__METHOD__, __LINE__]);

        $responseData = [];
        if ($response_status == 200) {
            $responseData = $this->suiteHelper->rawResponseToArray($data);
        }

        $response = [
            "status" => $response_status,
            "data" => $responseData
        ];

        return $this->_handleApiErrors($response, $expectedStatus, $errorMessage);
    }

    /**
     * @param $postData
     * @return string
     */
    private function arrayToQueryParams($postData)
    {
        $post_data_string = '';
        foreach ($postData as $_key => $_val) {
            $post_data_string .= $_key . '=' . urlencode(mb_convert_encoding($_val, 'ISO-8859-1', 'UTF-8')) . '&';
        }
        return $post_data_string;
    }
}
