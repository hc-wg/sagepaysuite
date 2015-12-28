<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Api;

/**
 * Sage Pay Reporting API parent class
 */
class ReportingApi
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
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param ApiExceptionFactory $apiExceptionFactory
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config
    ) {
        $this->_config = $config;
        $this->_curlFactory = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;
    }

    /**
     * Makes the Curl call and returns the xml response.
     *
     * @param string $xml description
     */
    public function executeRequest($xml) {

        $curl = $this->_curlFactory->create();

        $curl->setConfig(
            [
                'timeout'  => 120,
                'verifypeer' => false,
                'verifyhost' => 2
            ]
        );

        $curl->write(\Zend_Http_Client::POST,
            $this->_getServiceUrl(),
            '1.0',
            [],
            'XML=' . $xml);
        $data = $curl->read();
        if ($data === false) {
            return false;
        }
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $xml = new \SimpleXMLElement($data);
        } catch (\Exception $e) {
            return false;
        }

        return $xml;
    }

    /**
     * Returns url for each enviroment according the configuration.
     */
    protected function _getServiceUrl() {

        if($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE ){
            return \Ebizmarts\SagePaySuite\Model\Config::URL_REPORTING_API_LIVE;
        }else{
            return \Ebizmarts\SagePaySuite\Model\Config::URL_REPORTING_API_TEST;
        }
    }

    /**
     * Creates the connection's signature.
     *
     * @param string $command Param request to the API.
     * @return string MD5 hash signature.
     */
    protected function _getXmlSignature($command, $params) {

        $xml = '<command>' . $command . '</command>';
        $xml .= '<vendor>' . $this->_config->getVendorname() . '</vendor>';
        $xml .= '<user>' . $this->_config->getReportingApiUser() . '</user>';
        $xml .= $params;
        $xml .= '<password>' . $this->_config->getReportingApiPassword() . '</password>';


        return md5($xml);
    }

    /**
     * Creates the xml file to be used into the request.
     *
     * @param string $command API command.
     * @param string $params  Parameters used for each command.
     * @return string Xml string to be used into the API connection.
     */
    public function createXml($command, $params = null) {
        $xml = '';
        $xml .= '<vspaccess>';
        $xml .= '<command>' . $command . '</command>';
        $xml .= '<vendor>' . $this->_config->getVendorname() . '</vendor>';
        $xml .= '<user>' . $this->_config->getReportingApiUser() . '</user>';

        if (!is_null($params)) {
            $xml .= $params;
        }

        $xml .= '<signature>' . $this->_getXmlSignature($command, $params) . '</signature>';
        $xml .= '</vspaccess>';
        return $xml;
    }

    /**
     * Handle logical errors
     *
     * @param array $response
     * @return void
     * @throws \Ebizmarts\SagePaySuite\Model\Api\ProcessableException|\Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function handleApiErrors($response)
    {
        $exceptionPhrase = "Invalid response from Sage Pay API.";
        $exceptionCode = 0;

        if (!empty($response)) {
            if(!array_key_exists("errorcode",$response) || $response->errorcode == '0000'){

                //this is a successfull response
                return $response;

            }else{

                //there was an error
                $exceptionCode = $response->errorcode;
                if(array_key_exists("error",$response)) {
                    $exceptionPhrase = $response->error;
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