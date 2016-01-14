<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Class Config to handle all sagepay integrations configs
 */
class Config implements ConfigInterface
{

    /**
     * SagePaySuite Integration codes
     */
    const METHOD_FORM = 'sagepaysuiteform';
    const METHOD_PI = 'sagepaysuitepi';
    const METHOD_SERVER = 'sagepaysuiteserver';

    /**
     * Actions
     */
    const ACTION_PAYMENT = 'PAYMENT';
    const ACTION_PAYMENT_PI = 'Payment';
    const ACTION_DEFER = 'DEFERRED';
    const ACTION_AUTHENTICATE = 'AUTHENTICATE';
    const ACTION_VOID = 'VOID';
    const ACTION_REFUND = 'REFUND';
    const ACTION_RELEASE = 'RELEASE';
    const ACTION_AUTHORISE = 'AUTHORISE';
    const ACTION_POST = 'post';

    /**
     * SagePay MODES
     */
    const MODE_TEST = 'test';
    const MODE_LIVE = 'live';

    /**
     * 3D secure MODES
     */
    const MODE_3D_DEFAULT = 'UseMSPSetting';
    const MODE_3D_FORCE = 'Force';
    const MODE_3D_DISABLE = 'Disable';
    const MODE_3D_IGNORE = 'ForceIgnoringRules';

    /**
     * SagePay Vars map
     */
    const VAR_VendorTxCode = 'VendorTxCode';
    const VAR_VPSTxId = 'VPSTxId';
    const VAR_Status = 'Status';
    const VAR_StatusDetail = 'StatusDetail';
    const VAR_TxAuthNo = 'TxAuthNo';
    const VAR_AVSCV2 = 'AVSCV2';
    const VAR_AddressResult = 'AddressResult';
    const VAR_PostCodeResult = 'PostCodeResult';
    const VAR_CV2Result = 'CV2Result';
    const VAR_GiftAid = 'GiftAid';
    const VAR_3DSecureStatus = '3DSecureStatus';
    const VAR_CAVV = 'CAVV';
    const VAR_CardType = 'CardType';
    const VAR_Last4Digits = 'Last4Digits';
    const VAR_DeclineCode = 'DeclineCode';
    const VAR_ExpiryDate = 'ExpiryDate';
    const VAR_Amount = 'Amount';
    const VAR_BankAuthCode = 'BankAuthCode';
    const VAR_Crypt = 'crypt';

    /**
     * SagePay URLs
     */
    const URL_FORM_REDIRECT_LIVE = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
    const URL_FORM_REDIRECT_TEST = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
    const URL_PI_API_LIVE = 'https://live.sagepay.com/api/v1/';
    const URL_PI_API_TEST = 'https://test.sagepay.com/api/v1/';
    const URL_REPORTING_API_TEST = 'https://test.sagepay.com/access/access.htm';
    const URL_REPORTING_API_LIVE = 'https://live.sagepay.com/access/access.htm';
    const URL_SHARED_VOID_TEST = 'https://test.sagepay.com/gateway/service/void.vsp';
    const URL_SHARED_VOID_LIVE = 'https://live.sagepay.com/gateway/service/void.vsp';
    const URL_SHARED_REFUND_TEST = 'https://test.sagepay.com/gateway/service/refund.vsp';
    const URL_SHARED_REFUND_LIVE = 'https://live.sagepay.com/gateway/service/refund.vsp';
    const URL_SHARED_RELEASE_TEST = 'https://test.sagepay.com/gateway/service/release.vsp';
    const URL_SHARED_RELEASE_LIVE = 'https://live.sagepay.com/gateway/service/release.vsp';
    const URL_SHARED_AUTHORIZE_TEST = 'https://test.sagepay.com/gateway/service/authorise.vsp';
    const URL_SHARED_AUTHORIZE_LIVE = 'https://live.sagepay.com/gateway/service/authorise.vsp';
    const URL_SERVER_POST_TEST = 'https://test.sagepay.com/gateway/service/vspserver-register.vsp';
    const URL_SERVER_POST_LIVE = 'https://live.sagepay.com/gateway/service/vspserver-register.vsp';


    /**
     * SagePay Status Codes
     */
    const SUCCESS_STATUS = '0000';
    const AUTH3D_REQUIRED_STATUS = '2007';

    /**
     * Current payment method code
     *
     * @var string
     */
    protected $_methodCode;

    /**
     * Current payment method instance
     *
     * @var MethodInterface
     */
    protected $_methodInstance;

    /**
     * Current store id
     *
     * @var int
     */
    protected $_storeId;

    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    protected $_sourceCountry;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\Config\Source\Country $sourceCountry
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_sourceCountry = $sourceCountry;
    }

    /**
     * Method code setter
     *
     * @param string|MethodInterface $method
     * @return $this
     */
    public function setMethod($method)
    {
        if ($method instanceof MethodInterface) {
            $this->_methodCode = $method->getCode();
            $this->_methodInstance = $method;
        } elseif (is_string($method)) {
            $this->_methodCode = $method;
        }
        return $this;
    }

    /**
     * @param string $methodCode
     */
    public function setMethodCode($methodCode)
    {
        $this->_methodCode = $methodCode;
    }

    /**
     * Payment method instance code getter
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_methodCode;
    }

    /**
     * Store ID setter
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = (int)$storeId;
        return $this;
    }

    /**
     * Returns payment configuration value
     *
     * @param string $key
     * @param null $storeId
     * @return null|string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValue($key, $storeId = null)
    {
        if(is_null($storeId)){
            $storeId = $this->_storeId;
        }

        $path = $this->_getSpecificConfigPath($key);
        if ($path !== null) {
            $value = $this->_scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            return $value;
        }
        return null;
    }

    /**
     * Map any supported payment method into a config path by specified field name
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _getSpecificConfigPath($fieldName)
    {
        return "payment/{$this->_methodCode}/{$fieldName}";
    }

    protected function _getGlobalConfigPath($fieldName)
    {
        return "sagepaysuite/global/{$fieldName}";
    }

    protected function _getAdvancedConfigPath($fieldName)
    {
        return "sagepaysuite/advanced/{$fieldName}";
    }

    /**
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isMethodActive()
    {
        return $this->getValue("active");
    }

    /**
     * Check whether specified currency code is supported
     *
     * @param string $code
     * @return bool
     */
    public function isCurrencyCodeSupported($code)
    {
        return true;
    }

    public function getVPSProtocol()
    {
        return "3.00";
    }

    public function getSagepayPaymentAction()
    {
        $action = $this->getValue("payment_action");

        if ($this->_methodCode == self::METHOD_PI) {
            switch ($action) {
                case self::ACTION_PAYMENT:
                    return self::ACTION_PAYMENT_PI;
                    break;
                default:
                    return self::ACTION_PAYMENT_PI;
                    break;
            }
        } else {
//            switch ($action) {
//                case \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE:
//                    return self::ACTION_PAYMENT;
//                    break;
//                case \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE:
//                    return self::ACTION_DEFER;
//                    break;
//                default:
//                    return self::ACTION_PAYMENT;
//                    break;
//            }
            return $action;
        }
    }

    public function getPaymentAction()
    {
        $action = $this->getValue("payment_action");

        switch ($action) {
            case self::ACTION_PAYMENT:
                return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
                break;
            case self::ACTION_DEFER:
            case self::ACTION_AUTHENTICATE:
                return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE;
                break;
            default:
                return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
                break;
        }
    }

    public function getVendorname()
    {
        return $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("vendorname"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getLicense()
    {
        return $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("license"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getStoreDomain()
    {
        return $this->_scopeConfig->getValue(
            Store::XML_PATH_UNSECURE_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getFormEncryptedPassword()
    {
        return $this->getValue("encrypted_password");
    }

    public function getMode()
    {
        return $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("mode"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getTokenEnabled()
    {
        return $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("token"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getReportingApiUser()
    {
        return $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("reporting_user"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getReportingApiPassword()
    {
        return $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("reporting_password"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getPIPassword()
    {
        return $this->getValue("password");
    }

    public function getPIKey()
    {
        return $this->getValue("key");
    }

    /**
     * @param string $pathPattern
     */
    public function setPathPattern($pathPattern)
    {

    }

    public function get3Dsecure()
    {
        $config_value = $this->_scopeConfig->getValue(
            $this->_getAdvancedConfigPath("threedsecure"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );

        switch ($this->_methodCode) {
            case self::METHOD_PI:
                return $config_value;
                break;
            default:
                return $config_value;
                break;
        }
    }

    /**
     * @param $country
     * @return bool
     */
    public function canUseForCountry($country)
    {
        /*
        for specific country, the flag will set up as 1
        */
        if ($this->getValue("allowspecific") == 1) {
            $availableCountries = explode(',', $this->getValue("specificcountry"));
            if (!in_array($country, $availableCountries)) {
                return false;
            }
        }
        return true;
    }
}