<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Api;

/**
 * Sage Pay PI REST API
 */
class PIRestApi
{

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     *
     */
    protected $_curlFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory
     */
    protected $_apiExceptionFactory;

    /**
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory
    )
    {
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_curlFactory = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;
    }

    /**
     * Makes the Curl POST
     *
     * @param string $xml description
     */
    protected function _executeRequest($url, $body)
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

        $curl->write(\Zend_Http_Client::POST,
            $url,
            '1.0',
            array('Content-type: application/json'),
            $body);
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
     */
    protected function _getServiceUrl($enpoint)
    {
        if ($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_LIVE . $enpoint;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PI_API_TEST . $enpoint;
        }
    }

    public function generateMerchantKey()
    {
        $jsonBody = json_encode(array("vendorName" => $this->_config->getVendorname()));
        $result = $this->_executeRequest($this->_getServiceUrl("merchant-session-keys"), $jsonBody);

        if ($result["status"] == 201) {

            return $result["data"]->merchantSessionKey;

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

    public function capture($payment_request)
    {
        $jsonRequest = json_encode($payment_request);
        $result = $this->_executeRequest($this->_getServiceUrl("transactions"), $jsonRequest);

        if ($result["status"] == 201) {

            //success
            return $result["data"];

        }elseif ($result["status"] == 200) {

            //authentication required
            return $result["data"];

        } else {

            $error_code = $result["data"]->statusCode;
            $error_msg = $result["data"]->statusDetail;

            $exception = $this->_apiExceptionFactory->create([
                'phrase' => __($error_msg),
                'code' => $error_code
            ]);

            throw $exception;
        }
    }

}