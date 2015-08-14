<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Ebizmarts\SagePaySuite\Model\Config;

/**
 * Class AbstractConfig
 */
abstract class AbstractConfig implements ConfigInterface
{
    /**#@+
     * Payment actions
     */
    const PAYMENT_ACTION_PAYMENT = 'PAYMENT';

    const PAYMENT_ACTION_AUTH = 'Authorization';

    const PAYMENT_ACTION_ORDER = 'Order';

    /**
     * Current payment method code
     *
     * @var string
     */
    protected $_methodCode;

    /**
     * @var string
     */
    protected $pathPattern;

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
     * @var MethodInterface
     */
    protected $methodInstance;

    /**
     * Sets method instance used for retrieving method specific data
     *
     * @param MethodInterface $method
     * @return $this
     */
    public function setMethodInstance($method)
    {
        $this->methodInstance = $method;
        return $this;
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
        } elseif (is_string($method)) {
            $this->_methodCode = $method;
        }
        return $this;
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

    public function setMethodCode($methodCode)
    {
        $this->_methodCode = $methodCode;
    }

    /**
     * Sets path pattern
     *
     * @param string $pathPattern
     * @return void
     */
    public function setPathPattern($pathPattern)
    {
        $this->pathPattern = $pathPattern;
    }
}