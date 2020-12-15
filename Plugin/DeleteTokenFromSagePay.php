<?php

namespace Ebizmarts\SagePaySuite\Plugin;

use Ebizmarts\SagePaySuite\Model\Api\Post;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @throws NoSuchEntityException
     */
    public function deleteFromSagePay($token)
    {
        if (empty($this->config->getVendorname()) || empty($token)) {
            throw new NoSuchEntityException(
                __('Unable to delete token from Opayo: missing data to proceed')
            );
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
    }

    /**
     * @return string
     */
    private function _getRemoveServiceURL()
    {
        return $this->config->getMode() == Config::MODE_LIVE ? Config::URL_TOKEN_POST_REMOVE_LIVE : Config::URL_TOKEN_POST_REMOVE_TEST;
    }

}
