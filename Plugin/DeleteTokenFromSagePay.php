<?php

namespace Ebizmarts\SagePaySuite\Plugin;

use Ebizmarts\SagePaySuite\Model\Api\Post;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class DeleteTokenFromSagePay
{
    /** @var Post */
    private $postApi;

    /** @var Config */
    private $config;

    /** @var */
    private $suiteLogger;

    /**
     * DeleteTokenFromSagePay constructor.
     * @param Config $config
     * @param Post $postApi
     * @param Logger $suiteLogger
     */
    public function __construct(
        Config $config,
        Post $postApi,
        Logger $suiteLogger
    ) {
        $this->config      = $config;
        $this->postApi     = $postApi;
        $this->suiteLogger = $suiteLogger;
    }

    /**
     * delete token using Sage Pay API
     * @param string $token
     */
    public function deleteFromSagePay($token)
    {
        try {
            if (empty($this->config->getVendorname()) || empty($token)) {
                //missing data to proceed
                return;
            }

            //generate delete POST request
            $data = [];
            $data["VPSProtocol"] = $this->config->getVPSProtocol();
            $data["TxType"] = "REMOVETOKEN";
            $data["Vendor"] = $this->config->getVendorname();
            $data["Token"] = $token;

            //send POST to Sage Pay
            $this->postApi->sendPost(
                $data,
                $this->_getRemoveServiceURL(),
                ["OK"]
            );
        } catch (\Exception $e) {
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getMessage(), [__METHOD__, __LINE__]);
            //we do not show any error message to frontend
        }
    }

    /**
     * @return string
     */
    private function _getRemoveServiceURL()
    {
        if ($this->config->getMode() == Config::MODE_LIVE) {
            return Config::URL_TOKEN_POST_REMOVE_LIVE;
        } else {
            return Config::URL_TOKEN_POST_REMOVE_TEST;
        }
    }

}
