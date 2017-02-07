<?php

namespace Ebizmarts\SagePaySuite\Model\Api;


abstract class Http
{
    /** @var string */
    private $basicAuth;

    /** @var string */
    private $contentType;

    /** @var string */
    private $responseData;

    /** @var string */
    private $destinationUrl;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\HttpResponseInterface */
    private $returnData;

    /** @var integer */
    private $responseCode;

    /** @var \Magento\Framework\HTTP\Adapter\Curl */
    private $curl;

    /** @var \Ebizmarts\SagePaySuite\Model\Logger\Logger */
    private $logger;

    public function __construct(
        \Magento\Framework\HTTP\Adapter\Curl $curl,
        \Ebizmarts\SagePaySuite\Api\Data\HttpResponseInterface $returnData,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $logger
    ) {
        $this->curl        = $curl;
        $this->returnData  = $returnData;
        $this->logger      = $logger;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return integer
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\Data\HttpResponseInterface
     */
    public function getReturnData()
    {
        return $this->returnData;
    }

    public function setBasicAuth($username, $password)
    {
        $this->basicAuth = "$username:$password";
    }

    protected function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function setUrl($url)
    {
        $this->destinationUrl = $url;
    }

    public function initialize()
    {
        $config = [
            'timeout'    => 120,
            'verifypeer' => false,
            'verifyhost' => 2,
        ];

        if ($this->basicAuth !== null) {
            $config['userpwd'] = $this->basicAuth;
        }

        $this->curl->setConfig($config);
    }

    /**
     * @param $body
     * @return \Ebizmarts\SagePaySuite\Api\Data\HttpResponseInterface
     */
    public function executePost($body)
    {
        $this->initialize();

        $this->getLogger()->sageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_REQUEST, $body, [__METHOD__, __LINE__]);

        $this->curl->write(
            \Zend_Http_Client::POST,
            $this->destinationUrl,
            '1.0',
            ['Content-type: ' . $this->contentType],
            $body
        );
        $this->responseData = $this->curl->read();

        $this->responseCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $this->curl->close();

        return $this->processResponse();
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\Data\HttpResponseInterface
     */
    public function executeGet()
    {
        $this->initialize();

        $this->curl->write(
            \Zend_Http_Client::GET,
            $this->destinationUrl,
            '1.0',
            ['Content-type: ' . $this->contentType]
        );
        $this->responseData = $this->curl->read();

        $this->getLogger()->sageLog(\Ebizmarts\SagePaySuite\Model\Logger\Logger::LOG_REQUEST, $this->responseData, [__METHOD__, __LINE__]);

        $this->responseCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $this->curl->close();

        return $this->processResponse();
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Api\Data\HttpResponseInterface
     * @throws \Ebizmarts\SagePaySuite\Model\Api\ApiException
     */
    abstract public function processResponse();
}