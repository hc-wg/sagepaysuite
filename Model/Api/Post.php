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

    /** @var \Ebizmarts\SagePaySuite\Model\Api\HttpText  */
    private $httpTextFactory;

    /**
     * Post constructor.
     * @param HttpTextFactory $httpTextFactory
     * @param ApiExceptionFactory $apiExceptionFactory
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param Logger $suiteLogger
     * @param \Ebizmarts\SagePaySuite\Helper\Request $suiteHelper
     */
    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Api\HttpTextFactory $httpTextFactory,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Request $suiteHelper
    ) {
        $this->_config              = $config;
        $this->_apiExceptionFactory = $apiExceptionFactory;
        $this->_suiteLogger         = $suiteLogger;
        $this->suiteHelper          = $suiteHelper;
        $this->httpTextFactory      = $httpTextFactory;
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
        /** @var \Ebizmarts\SagePaySuite\Model\Api\HttpText $rest */
        $rest = $this->httpTextFactory->create();

        $body = $rest->arrayToQueryParams($postData);

        $rest->setUrl($url);
        $response = $rest->executePost($body);

        $responseData = [];
        if ($response->getStatus() == 200) {
            $responseData = $rest->rawResponseToArray();
        }

        $response = [
            "status" => $response->getStatus(),
            "data"   => $responseData
        ];

        return $this->_handleApiErrors($response, $expectedStatus, $errorMessage);
    }
}
