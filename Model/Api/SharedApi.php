<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Api;

/**
 * Sage Pay Reporting API parent class
 */
class SharedApi
{

//    /**
//     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
//     *
//     */
//    protected $_curlFactory;
//
//    /**
//     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory
//     */
//    protected $_apiExceptionFactory;
//
//    /**
//     * @var \Ebizmarts\SagePaySuite\Model\Config
//     */
//    protected $_config;
//
//    /**
//     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
//     * @param ApiExceptionFactory $apiExceptionFactory
//     * @param \Ebizmarts\SagePaySuite\Model\Config $config
//     */
//    public function __construct(
//        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
//        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
//        \Ebizmarts\SagePaySuite\Model\Config $config
//    ) {
//        $this->_config = $config;
//        $this->_curlFactory = $curlFactory;
//        $this->_apiExceptionFactory = $apiExceptionFactory;
//    }
//
//    /**
//     * Makes the Curl call and returns the response.
//     *
//     * @param string $xml description
//     */
//    protected function _executeRequest($url, $data)
//    {
//
//        $curl = $this->_curlFactory->create();
//
//        $curl->setConfig(
//            [
//                'timeout' => 120,
//                'verifypeer' => false,
//                'verifyhost' => 2
//            ]
//        );
//
//        $postData = "";
//        foreach ($data as $_key => $_val) {
//            $postData .= $_key . '=' . urlencode(mb_convert_encoding($_val, 'ISO-8859-1', 'UTF-8')) . '&';
//        }
//
//        $curl->write(\Zend_Http_Client::POST,
//            $url,
//            '1.0',
//            [],
//            $postData);
//        $data = $curl->read();
//
//        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
//        $curl->close();
//
//        $data = preg_split('/^\r?$/m', $data, 2);
//        $data = json_decode(trim($data[1]));
//
//        $response = [
//            "status" => $response_status,
//            "data" => $data
//        ];
//
//        return $response;
//    }
//
//    /**
//     * Returns url for each enviroment according the configuration.
//     */
//    protected function _getServiceUrl($action) {
//
//        switch($action){
//            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_VOID:
//                if($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE ){
//                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_VOID_LIVE;
//                }else{
//                    return \Ebizmarts\SagePaySuite\Model\Config::URL_SHARED_VOID_TEST;
//                }
//                break;
//        }
//    }

}