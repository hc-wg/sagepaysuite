<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

/**
 * Class Config to handle all sagepay integrations configs
 */
class Config
{
    /**
     * SagePay VPS protocol
     */
    const VPS_PROTOCOL = '3.00';

    /**
     * SagePaySuite Integration codes
     */
    const METHOD_FORM = 'sagepaysuiteform';
    const METHOD_PI = 'sagepaysuitepi';
    const METHOD_SERVER = 'sagepaysuiteserver';
    const METHOD_PAYPAL = 'sagepaysuitepaypal';
    const METHOD_REPEAT = 'sagepaysuiterepeat';

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
    const ACTION_REPEAT = 'REPEAT';
    const ACTION_REPEAT_DEFERRED = 'REPEATDEFERRED';
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
    const MODE_3D_DEFAULT = 'UseMSPSetting'; // '0' for old integrations
    const MODE_3D_FORCE = 'Force'; // '1' for old integrations
    const MODE_3D_DISABLE = 'Disable'; // '2' for old integrations
    const MODE_3D_IGNORE = 'ForceIgnoringRules'; // '3' for old integrations

    /**
     * AvsCvc MODES
     */
    const MODE_AVSCVC_DEFAULT = 'UseMSPSetting'; // '0' for old integrations
    const MODE_AVSCVC_FORCE = 'Force'; // '1' for old integrations
    const MODE_AVSCVC_DISABLE = 'Disable'; // '2' for old integrations
    const MODE_AVSCVC_IGNORE = 'ForceIgnoringRules'; // '3' for old integrations

    /**
     * FORM Send Email MODES
     */
    const MODE_FORM_SEND_EMAIL_NONE = 0; //  Do not send either customer or vendor emails
    const MODE_FORM_SEND_EMAIL_BOTH = 1; // Send customer and vendor emails if addresses are provided
    const MODE_FORM_SEND_EMAIL_ONLY_VENDOR = 2; //  Send vendor email but NOT the customer email

    /**
     * Currency settings
     */
    const CURRENCY_BASE = "base_currency";
    const CURRENCY_STORE = "store_currency";
    const CURRENCY_SWITCHER = "switcher_currency";

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
    const URL_SHARED_REPEAT_TEST = 'https://test.sagepay.com/gateway/service/repeat.vsp';
    const URL_SHARED_REPEAT_LIVE = 'https://live.sagepay.com/gateway/service/repeat.vsp';
    const URL_SERVER_POST_TEST = 'https://test.sagepay.com/gateway/service/vspserver-register.vsp';
    const URL_SERVER_POST_LIVE = 'https://live.sagepay.com/gateway/service/vspserver-register.vsp';
    const URL_DIRECT_POST_TEST = 'https://test.sagepay.com/gateway/service/vspdirect-register.vsp';
    const URL_DIRECT_POST_LIVE = 'https://live.sagepay.com/gateway/service/vspdirect-register.vsp';
    const URL_PAYPAL_COMPLETION_TEST = 'https://test.sagepay.com/gateway/service/complete.vsp';
    const URL_PAYPAL_COMPLETION_LIVE = 'https://live.sagepay.com/gateway/service/complete.vsp';
    const URL_TOKEN_POST_REMOVE_LIVE = 'https://live.sagepay.com/gateway/service/removetoken.vsp';
    const URL_TOKEN_POST_REMOVE_TEST = 'https://test.sagepay.com/gateway/service/removetoken.vsp';

    /**
     * SagePay Status Codes
     */
    const SUCCESS_STATUS = '0000';
    const AUTH3D_REQUIRED_STATUS = '2007';

    /**
     * SagePay Third Man Score Statuses
     */
    const T3STATUS_NORESULT = 'NORESULT';
    const T3STATUS_OK = 'OK';
    const T3STATUS_HOLD = 'HOLD';
    const T3STATUS_REJECT = 'REJECT';

    /**
     * SagePay ReD Score Statuses
     */
    const ReDSTATUS_ACCEPT = 'ACCEPT';
    const ReDSTATUS_DENY = 'DENY';
    const ReDSTATUS_CHALLENGE = 'CHALLENGE';
    const ReDSTATUS_NOTCHECKED = 'NOTCHECKED';


    /**
     * Basket Formats
     */
    const BASKETFORMAT_Sage50 = 'Sage50';
    const BASKETFORMAT_XML = 'xml';
    const BASKETFORMAT_Disabled = 'Disabled';

    /*
     * Max tokens per customer
     */
    const MAX_TOKENS_PER_CUSTOMER = 3;

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
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger
    ) {
    
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_suiteLogger = $suiteLogger;
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
        if (is_null($storeId)) {
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

    public function getVPSProtocol()
    {
        return self::VPS_PROTOCOL;
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
            return $action;
        }
    }

    public function getPaymentAction()
    {
        $action = $this->getValue("payment_action");

        switch ($action) {
            case self::ACTION_PAYMENT:
            case self::ACTION_REPEAT:
                return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
                break;
            case self::ACTION_DEFER:
            case self::ACTION_AUTHENTICATE:
            case self::ACTION_REPEAT_DEFERRED:
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

    /**
     * @return null|string
     */
    public function getFormEncryptedPassword()
    {
        return $this->getValue("encrypted_password");
    }

    public function getFormSendEmail()
    {
        return $this->getValue("send_email");
    }

    public function getFormVendorEmail()
    {
        return $this->getValue("vendor_email");
    }

    public function getFormEmailMessage()
    {
        return $this->getValue("email_message");
    }

    public function getMode()
    {
        return $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("mode"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function isTokenEnabled()
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
     * return 3D secure rules setting
     * @return string
     */
    public function get3Dsecure($forceDisable = false)
    {
        $config_value = $this->_scopeConfig->getValue(
            $this->_getAdvancedConfigPath("threedsecure"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );

        if ($forceDisable) {
            $config_value = self::MODE_3D_DISABLE;
        }

        if ($this->_methodCode == self::METHOD_PI) {
            return $config_value;
        } else {
            //for old integrations
            switch ($config_value) {
                case self::MODE_3D_FORCE:
                    return '1';
                    break;
                case self::MODE_3D_DISABLE:
                    return '2';
                    break;
                case self::MODE_3D_IGNORE:
                    return '3';
                    break;
                default:
                    return '0';
                    break;
            }
        }
    }

    /**
     * return AVS_CVC rules setting
     * @return string
     */
    public function getAvsCvc()
    {
        $config_value = $this->_scopeConfig->getValue(
            $this->_getAdvancedConfigPath("avscvc"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );

        if ($this->_methodCode == self::METHOD_PI) {
            return $config_value;
        } else {
            //for old integrations
            switch ($config_value) {
                case self::MODE_AVSCVC_FORCE:
                    return '1';
                    break;
                case self::MODE_AVSCVC_DISABLE:
                    return '2';
                    break;
                case self::MODE_AVSCVC_IGNORE:
                    return '3';
                    break;
                default:
                    return '0';
                    break;
            }
        }
    }

    public function getAutoInvoiceFraudPassed()
    {
        $config_value = $this->_scopeConfig->getValue(
            $this->_getAdvancedConfigPath("fraud_autoinvoice"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
        return $config_value;
    }

    public function getNotifyFraudResult()
    {
        $config_value = $this->_scopeConfig->getValue(
            $this->_getAdvancedConfigPath("fraud_notify"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
        return $config_value;
    }

    public function getPaypalBillingAgreement()
    {
        return $this->getValue("billing_agreement");
    }

    public function getAllowedCcTypes()
    {
        return $this->getValue("cctypes");
    }

    public function getAreSpecificCountriesAllowed()
    {
        return $this->getValue("allowspecific");
    }

    public function getSpecificCountries()
    {
        return $this->getValue("specificcountry");
    }

    public function getCurrencyCode()
    {
        $currency_settings = $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("currency"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );

        //store base currency as default
        $currency = $this->_storeManager->getStore()->getBaseCurrencyCode();

        switch ($currency_settings) {
            //store default display currency
            case \Ebizmarts\SagePaySuite\Model\Config::CURRENCY_STORE:
                $currency = $this->_storeManager->getStore()->getDefaultCurrencyCode();
                break;

            //frontend currency switcher
            case \Ebizmarts\SagePaySuite\Model\Config::CURRENCY_SWITCHER:
                $currency = $this->_storeManager->getStore()->getCurrentCurrencyCode();
                break;
        }

        return $currency;
    }

    public function getCurrencyConfig()
    {
        return $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("currency"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getBasketFormat()
    {
        $config_value = $this->_scopeConfig->getValue(
            $this->_getAdvancedConfigPath("basket_format"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
        return $config_value;
    }

    public function isPaypalForceXml()
    {
        return $this->getValue("force_xml");
    }

    public function isGiftAidEnabled()
    {
        $config_value = $this->_scopeConfig->getValue(
            $this->_getAdvancedConfigPath("giftaid"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
        return $config_value;
    }

    public function isServerLowProfileEnabled()
    {
        return $this->getValue("profile");
    }

    /**
     * @param $methodCode
     * @return bool
     */
    public function isSagePaySuiteMethod($methodCode)
    {
        return $methodCode == self::METHOD_PAYPAL ||
        $methodCode == self::METHOD_PI ||
        $methodCode == self::METHOD_FORM ||
        $methodCode == self::METHOD_SERVER ||
        $methodCode == self::METHOD_REPEAT;
    }
}
