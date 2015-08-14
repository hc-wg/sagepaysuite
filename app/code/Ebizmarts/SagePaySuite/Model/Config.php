<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Store\Model\ScopeInterface;

/**
 * Config model that is aware of all \Ebizmarts\SagePaySuite payment methods
 * @SuppressWarnings(PHPMD.ExcesivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Config extends AbstractConfig
{
    /**
     * SagePaySuite FORM
     */
    const METHOD_FORM = 'sagepaysuiteform';

    /**
     * SagePay MODES
     */
    const MODE_TEST = 'test';
    const MODE_LIVE = 'live';

    /**
     * SagePay ACTIONS
     */
    const ACTION_POST = 'post';
    const ACTION_RELEASE = 'release';
    const ACTION_ABORT = 'abort';
    const ACTION_REFUND = 'refund';
    const ACTION_VOID = 'void';
    const ACTION_CANCEL = 'cancel';
    const ACTION_AUTHORISE = 'authorise';
    const ACTION_REPEAT = 'repeat';

    /**
     * SagePay Vars
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
     * Check whether specified currency code is supported
     *
     * @param string $code
     * @return bool
     */
    public function isCurrencyCodeSupported($code)
    {
        return true;
    }

    public function getSagePayFormUrl($mode, $action){
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
}
