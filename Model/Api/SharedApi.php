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
class SharedApi
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
        Logger $suiteLogger
    ) {
        $this->_config = $config;
        $this->_curlFactory = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;
        $this->_suiteLogger = $suiteLogger;
    }

    /**
     * Makes the Curl call and returns the response.
     *
     * @param string $xml description
     */
    public function executeRequest($action, $data)
    {
        $url = $this->_getServiceUrl($action);

        $curl = $this->_curlFactory->create();

        $curl->setConfig(
            [
                'timeout' => 120,
                'verifypeer' => false,
                'verifyhost' => 2
            ]
        );

        $postData = "";
        foreach ($data as $_key => $_val) {
            $postData .= $_key . '=' . urlencode(mb_convert_encoding($_val, 'ISO-8859-1', 'UTF-8')) . '&';
        }
        $curl->write(\Zend_Http_Client::POST,
            $url,
            '1.0',
            [],
            $postData);
        $data = $curl->read();

        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
        $curl->close();

        //parse response
        $response_data = [];
        if($response_status == 200){
            $data = preg_split('/^\r?$/m', $data, 2);
            $data = explode('\n', $data[1]);
            for($i=0;$i<count($data);$i++){
                if(!empty($data[$i])){
                    $aux = explode("=",trim($data[$i]));
                    if(count($aux) == 2){
                        $response_data[$aux[0]] = $aux[1];
                    }
                }
            }
        }else{
            $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $data);
        }

        $response = [
            "status" => $response_status,
            "data" => $response_data
        ];
        return $response;
    }

    /**
     * Returns url for each enviroment according the configuration.
     */
    protected function _getServiceUrl($action) {

        switch($action){
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_VOID:
                if($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE ){
                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_VOID_LIVE;
                }else{
                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_VOID_TEST;
                }
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND:
                if($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE ){
                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_REFUND_LIVE;
                }else{
                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_REFUND_TEST;
                }
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_RELEASE:
                if($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE ){
                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_RELEASE_LIVE;
                }else{
                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_RELEASE_TEST;
                }
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHORISE:
                if($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE ){
                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_AUTHORIZE_LIVE;
                }else{
                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_AUTHORIZE_TEST;
                }
                break;
            default:
                return null;

        }
    }

    public function handleApiErrors($response)
    {
        $exceptionPhrase = "Invalid response from Sage Pay API.";
        $exceptionCode = 0;

        if (!empty($response) && array_key_exists("data",$response)) {
            if(array_key_exists("Status",$response["data"]) && $response["data"]["Status"] == 'OK'){

                //this is a successfull response
                return $response;

            }else{

                //there was an error
                if(array_key_exists("StatusDetail",$response["data"])){
                    $detail = explode(":",$response["data"]["StatusDetail"]);
                    $exceptionCode = trim($detail[0]);
                    $exceptionPhrase = trim($detail[1]);
                }
            }
        }

        $exception = $this->_apiExceptionFactory->create([
            'phrase' => __($exceptionPhrase),
            'code' => $exceptionCode
        ]);

        throw $exception;
    }
}