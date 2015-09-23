<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;

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

    /**
     * Payment actions
     */
    const PAYMENT_ACTION_PAYMENT = 'PAYMENT';
    const PAYMENT_ACTION_DEFER = 'DEFER';
    const PAYMENT_ACTION_AUTHENTICATE = 'AUTHENTICATE';

    /**
     * SagePay MODES
     */
    const MODE_TEST = 'test';
    const MODE_LIVE = 'live';

    /**
     * SagePay ACTIONS
     */
    const ACTION_POST = 'post';

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
    const URL_PI_API_LIVE = 'https://live.sagepay.com/api/v1/';
    const URL_PI_API_TEST = 'https://test.sagepay.com/api/v1/';

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
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * To check billing country is allowed for the payment method
     *
     * @param string $country
     * @return bool
     */
    public function canUseForCountry($country)
    {
        /*
        for specific country, the flag will set up as 1
        */
//        if ($this->getConfigData(self::KEY_ALLOW_SPECIFIC) == 1) {
//            $availableCountries = explode(',', $this->getConfigData(self::KEY_SPECIFIC_COUNTRY));
//            if (!in_array($country, $availableCountries)) {
//                return false;
//            }
//        } elseif ($this->sourceCountry->isCountryRestricted($country)) {
//            return false;
//        }
        return true;
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
    public function setMethodCode($methodCode){
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
        switch ($key) {
            case 'getDebugReplacePrivateDataKeys':
                return $this->methodInstance->getDebugReplacePrivateDataKeys();
            default:
                $underscored = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $key));
                $path = $this->_getSpecificConfigPath($underscored);
                if ($path !== null) {
                    $value = $this->_scopeConfig->getValue(
                        $path,
                        ScopeInterface::SCOPE_STORE,
                        $this->_storeId
                    );
                    //$value = $this->_prepareValue($underscored, $value);
                    return $value;
                }
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
        return "sagepaysuite/{$this->_methodCode}/{$fieldName}";
    }

    protected function _getGlobalConfigPath($fieldName)
    {
        return "sagepaysuite/global/{$fieldName}";
    }

    protected function _getReportingApiConfigPath($fieldName)
    {
        return "sagepaysuite/reportingapi/{$fieldName}";
    }

    protected function _getPaymentConfigPath($fieldName)
    {
        return "payment/{$this->_methodCode}/{$fieldName}";
    }

    /**
     * Check whether method available for checkout or not
     *
     * @param null $methodCode
     *
     * @return bool
     */
    public function isMethodAvailable($methodCode = null)
    {
        $methodCode = $methodCode ?: $this->_methodCode;

        return $this->isMethodActive($methodCode);
    }

    /**
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @param string $method Method code
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isMethodActive($method)
    {
        $isEnabled = false;
        switch ($method) {
            case Config::METHOD_FORM:
                $isEnabled = $this->_scopeConfig->isSetFlag(
                    'sagepaysuite/' . Config::METHOD_FORM . '/active',
                    ScopeInterface::SCOPE_STORE,
                    $this->_storeId
                );
                break;
        }

        return $this->isMethodSupportedForCountry($method) && $isEnabled;
    }



    /**
     * Check whether method supported for specified country or not
     *
     * @param string|null $method
     * @param string|null $countryCode
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isMethodSupportedForCountry($method = null, $countryCode = null)
    {
        return true;
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

    public function getSagePayUrl($mode = self::MODE_LIVE, $action = self::ACTION_POST){

        if($this->getMethodCode() == self::METHOD_FORM){

            if($mode == self::MODE_LIVE){
                switch($action){
                    case self::ACTION_POST:
                        return 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
                        break;
                    default:
                        return null;
                        break;
                }
            }elseif($mode == self::MODE_TEST){
                switch($action){
                    case self::ACTION_POST:
                        return 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
                        break;
                    default:
                        return null;
                        break;
                }
            }
        }

        return null;
    }

    public function getVPSProtocol(){
        return "3.00";
    }

    public function getPaymentAction(){
        $action = $this->getValue("payment_action");
        $action = empty($action) ? self::PAYMENT_ACTION_PAYMENT : $action;

        switch($action){
            case self::PAYMENT_ACTION_PAYMENT:
                return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
                break;
            default:
                return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
                break;
        }
    }

    public function getSagepayPaymentAction(){
        $action = $this->getValue("payment_action");

        return empty($action) ? self::PAYMENT_ACTION_PAYMENT : $action;
    }

    public function getVendorname(){
        return $this->_scopeConfig->getValue(
            $this->_getGlobalConfigPath("vendorname"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getFormEncryptedPassword(){
        return $this->getValue("encrypted_password");
    }

    public function getMode(){
        return $this->getValue("mode");
    }

    public function getReportingApiMode(){
        return $this->_scopeConfig->getValue(
            $this->_getReportingApiConfigPath("reporting_mode"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getReportingApiUser(){
        return $this->_scopeConfig->getValue(
            $this->_getReportingApiConfigPath("reporting_user"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getReportingApiPassword(){
        return $this->_scopeConfig->getValue(
            $this->_getReportingApiConfigPath("reporting_password"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getPIPassword(){
        return $this->_scopeConfig->getValue(
            $this->_getPaymentConfigPath("password"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getPIKey(){
        return $this->_scopeConfig->getValue(
            $this->_getPaymentConfigPath("key"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getPIMode(){
        return $this->_scopeConfig->getValue(
            $this->_getPaymentConfigPath("mode"),
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }



    /**
     * @param string $pathPattern
     */
    public function setPathPattern($pathPattern){

    }
}