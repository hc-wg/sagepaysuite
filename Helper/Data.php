<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\Module\ModuleList\Loader
     */
    protected $_loader;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\Module\ModuleList\Loader $loader,
        \Magento\Framework\App\Helper\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config
    ) {
        parent::__construct($context);
        $this->_loader  = $loader;
        $this->_config  = $config;
    }

    /**
     * Get default sagepay config instance
     * @return \Ebizmarts\SagePaySuite\Model\Config
     */
    public function getSagePayConfig()
    {
        return $this->_config;
    }

    /**
     * @param string $order_id
     * @param string $action
     * @return string
     */
    public function generateVendorTxCode($order_id = "", $action = \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT)
    {

        $prefix = "";
        switch ($action) {
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_REFUND:
                $prefix = "R";
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHORISE:
                $prefix = "A";
                break;
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_REPEAT:
            case \Ebizmarts\SagePaySuite\Model\Config::ACTION_REPEAT_DEFERRED:
                $prefix = "RT";
                break;
        }

        return substr($prefix . $order_id . "-" . date('Y-m-d-His') . time(), 0, 40);
    }

    /**
     * Verify license
     * @return bool
     */
    public function verify()
    {
        $storeId = (int)$this->_getRequest()->getParam('store', \Magento\Store\Model\Store::DEFAULT_STORE_ID);
        $this->_config->setStoreId($storeId);
        $domain = preg_replace("/^http:\/\//", "", $this->_config->getStoreDomain());
        $domain = preg_replace("/^https:\/\//", "", $domain);
        $domain = preg_replace("/^www\./", "", $domain);
        $domain = preg_replace("/\/$/", "", $domain);
        $version = explode('.', $this->getVersion());
        $module = 'Ebizmarts_SagePaySuite2';
        $md5 = md5($module . $version[0].'.'.$version[1] . $domain);
        $key = hash('sha1', $md5 . 'EbizmartsV2');
        return ($key == $this->_config->getLicense());
    }

    /**
     * Get module version
     * @return string
     */
    public function getVersion()
    {
        $modules = $this->_loader->load();
        $v = "UNKNOWN";

        if (isset($modules['Ebizmarts_SagePaySuite']) && isset($modules['Ebizmarts_SagePaySuite']['setup_version'])) {
            $v =$modules['Ebizmarts_SagePaySuite']['setup_version'];
        }
        return $v;
    }

    /**
     * Stripe transaction if from '-capture/-refund/etc' appends
     * @param $transactionId
     * @return mixed
     */
    public function clearTransactionId($transactionId)
    {
        $suffixes = [
            '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
            '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID,
            '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND,
        ];
        foreach ($suffixes as $suffix) {
            if (strpos($transactionId, $suffix) !== false) {
                $transactionId = str_replace($suffix, '', $transactionId);
            }
        }
        return $transactionId;
    }
}
