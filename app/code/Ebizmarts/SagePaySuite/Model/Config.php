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
     * Check whether the specified payment method is a CC-based one
     *
     * @param string $code
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public static function getIsCreditCardMethod($code)
    {
        //still no cc method
//        switch ($code) {
//            case self::METHOD_FORM:
//                return true;
//        }
        return false;
    }

    /**
     * Check whether specified currency code is supported
     *
     * @param string $code
     * @return bool
     */
    public function isCurrencyCodeSupported($code)
    {
//        if (in_array($code, $this->_supportedCurrencyCodes)) {
//            return true;
//        }
//        if ($this->getMerchantCountry() == 'BR' && $code == 'BRL') {
//            return true;
//        }
//        if ($this->getMerchantCountry() == 'MY' && $code == 'MYR') {
//            return true;
//        }
//        if ($this->getMerchantCountry() == 'TR' && $code == 'TRY') {
//            return true;
//        }
        return true;
    }

    /**
     * Mapper from Sagepay-specific payment actions to Magento payment actions
     *
     * @return string|null
     */
//    public function getPaymentAction()
//    {
//        switch ($this->getValue('paymentAction')) {
//            case self::PAYMENT_ACTION_AUTH:
//                return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE;
//            case self::PAYMENT_ACTION_SALE:
//                return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
//            case self::PAYMENT_ACTION_ORDER:
//                return \Magento\Payment\Model\Method\AbstractMethod::ACTION_ORDER;
//        }
//        return null;
//    }

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

        if(empty($action)){
            return self::PAYMENT_ACTION_PAYMENT;
        }else{
            return $action;
        }
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
}
