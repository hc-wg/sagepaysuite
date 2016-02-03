<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
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
    protected $_curlFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory
     */
    protected $_apiExceptionFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param ApiExceptionFactory $apiExceptionFactory
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger
    )
    {
        $this->_config = $config;
        $this->_curlFactory = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;
        $this->_suiteLogger = $suiteLogger;
    }

    protected function _handleApiErrors($response, $expectedStatus, $defaultErrorMessage)
    {
        $success = false;

        if (!empty($response) &&
            $response["status"] == 200 &&
            array_key_exists("data", $response) &&
            array_key_exists("Status", $response["data"])
        ) {
            //check against all possible success response statuses
            for ($i = 0; $i < count($expectedStatus); $i++) {
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
     * @param string $defaultErrorMessage
     * @return mixed
     * @throws
     */
    public function sendPost($postData, $url, $expectedStatus = array(), $defaultErrorMessage = "Invalid response from Sage Pay")
    {

        $curl = $this->_curlFactory->create();

        $post_data_string = '';
        foreach ($postData as $_key => $_val) {
            $post_data_string .= $_key . '=' . urlencode(mb_convert_encoding($_val, 'ISO-8859-1', 'UTF-8')) . '&';
        }

        //log request
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $postData);

        $curl->setConfig(
            [
                'timeout' => 120,
                'verifypeer' => false,
                'verifyhost' => 2
            ]
        );

        $curl->write(\Zend_Http_Client::POST,
            $url,
            '1.0',
            [],
            $post_data_string);
        $data = $curl->read();

        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
        $curl->close();

        //log response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $data);

        $response_data = [];
        if ($response_status == 200) {

            //parse response
            $data = preg_split('/^\r?$/m', $data, 2);
            $data = explode('\n', $data[1]);

            for ($i = 0; $i < count($data); $i++) {
                if (!empty($data[$i])) {
                    $aux = explode("=", trim($data[$i]));
                    if (count($aux) == 2) {
                        $response_data[$aux[0]] = $aux[1];
                    } else {
                        if (count($aux) > 2) {
                            $response_data[$aux[0]] = $aux[1];
                            for ($j = 2; $j < count($aux); $j++) {
                                $response_data[$aux[0]] .= "=" . $aux[$j];
                            }
                        }
                    }
                }
            }
        }

        $response = [
            "status" => $response_status,
            "data" => $response_data
        ];

        return $this->_handleApiErrors($response, $expectedStatus, $defaultErrorMessage);
    }
}