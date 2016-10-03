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
class Reporting
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
     * @var Logger
     */
    private $_suiteLogger;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager
     */
    private $objectManager;

    /**
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param ApiExceptionFactory $apiExceptionFactory
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        Logger $suiteLogger,
        \Magento\Framework\ObjectManager\ObjectManager $objectManager
    ) {

        $this->_config              = $config;
        $this->_curlFactory         = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;
        $this->_suiteLogger         = $suiteLogger;
        $this->objectManager        = $objectManager;
    }

    /**
     * @param $xml
     * @return bool|\SimpleXMLElement
     */
    private function _executeRequest($xml)
    {
        $curl = $this->_curlFactory->create();

        $curl->setConfig(
            [
                'timeout' => 120,
                'verifypeer' => false,
                'verifyhost' => 2
            ]
        );

        $curl->write(
            \Zend_Http_Client::POST,
            $this->_getServiceUrl(),
            '1.0',
            [],
            'XML=' . $xml
        );
        $data = $curl->read();
        if ($data === false) {
            return false;
        }
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $xml = $this->objectManager->create('\SimpleXMLElement', ['data' => $data]);
        } catch (\Exception $e) {
            return false;
        }

        return $xml;
    }

    /**
     * Returns url for each enviroment according the configuration.
     */
    private function _getServiceUrl()
    {
        if ($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_REPORTING_API_LIVE;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_REPORTING_API_TEST;
        }
    }

    /**
     * Creates the connection's signature.
     *
     * @param string $command Param request to the API.
     * @return string MD5 hash signature.
     */
    private function _getXmlSignature($command, $params)
    {
        $xml = '<command>' . $command . '</command>';
        $xml .= '<vendor>' . $this->_config->getVendorname() . '</vendor>';
        $xml .= '<user>' . $this->_config->getReportingApiUser() . '</user>';
        $xml .= $params;
        $xml .= '<password>' . $this->_config->getReportingApiPassword() . '</password>';

        return hash('md5', $xml);
    }

    /**
     * Creates the xml file to be used into the request.
     *
     * @param string $command API command.
     * @param string $params Parameters used for each command.
     * @return string Xml string to be used into the API connection.
     */
    private function _createXml($command, $params = null)
    {
        $xml = '';
        $xml .= '<vspaccess>';
        $xml .= '<command>' . $command . '</command>';
        $xml .= '<vendor>' . $this->_config->getVendorname() . '</vendor>';
        $xml .= '<user>' . $this->_config->getReportingApiUser() . '</user>';

        if ($params !== null) {
            $xml .= $params;
        }

        $xml .= '<signature>' . $this->_getXmlSignature($command, $params) . '</signature>';
        $xml .= '</vspaccess>';
        return $xml;
    }

    /**
     * @param $response
     * @return mixed
     * @throws
     */
    private function _handleApiErrors($response)
    {
        //parse xml as object
        $response = (object)((array)$response);

        $exceptionPhrase = "Invalid response from Sage Pay API.";
        $exceptionCode = 0;
        $validResponse = false;

        if (!empty($response)) {
            if (is_object($response) && !array_key_exists("errorcode", $response) || $response->errorcode == '0000') {
                //this is a successfull response
                return $response;
            } else { //there was an error
                if (is_object($response) && array_key_exists("errorcode", $response)) {
                    $exceptionCode = $response->errorcode;
                    if (array_key_exists("error", $response)) {
                        $exceptionPhrase = $response->error;
                        $validResponse = true;
                    }
                }
            }
        }

        if (!$validResponse) {
            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $response);
        }

        $exception = $this->_apiExceptionFactory->create([
            'phrase' => __($exceptionPhrase),
            'code' => $exceptionCode
        ]);

        throw $exception;
    }

    /**
     * This command returns all information held in Sage Pay about the specified transaction.
     *
     * @param $vpstxid
     * @return mixed
     * @throws
     */
    public function getTransactionDetails($vpstxid)
    {
        $params = '<vpstxid>' . $vpstxid . '</vpstxid>';
        $xml = $this->_createXml('getTransactionDetail', $params);
        return $this->_handleApiErrors($this->_executeRequest($xml));
    }

    /**
     * This command returns the number of tokens the vendor currently has.
     *
     * @return mixed
     * @throws
     */
    public function getTokenCount()
    {
        $params = '';
        $xml = $this->_createXml('getTokenCount', $params);
        return $this->_handleApiErrors($this->_executeRequest($xml));
    }

    /**
     * This command returns the fraud screening details for a particular transaction.
     * The recommendation is returned along with details of the specific fraud rules
     * triggered by the transaction.
     *
     * @param $vpstxid
     * @return mixed
     * @throws
     */
    public function getFraudScreenDetail($vpstxid)
    {
        $params = '<vpstxid>' . $vpstxid . '</vpstxid>';
        $xml = $this->_createXml('getFraudScreenDetail', $params);
        return $this->_handleApiErrors($this->_executeRequest($xml));
    }

    /**
     * Get version of Reporting API (Used to validate credentials)
     *
     * @return mixed
     * @throws
     */
    public function getVersion()
    {
        $xml = $this->_createXml('version');
        return $this->_handleApiErrors($this->_executeRequest($xml));
    }
}
