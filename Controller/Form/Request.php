<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Request extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $_suiteHelper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * Sage Pay Suite Request Helper
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    protected $_requestHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->_suiteHelper = $suiteHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_requestHelper = $requestHelper;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_quote = $this->_checkoutSession->getQuote();
    }

    public function execute()
    {
        try {
            $this->_quote->collectTotals();
            $this->_quote->reserveOrderId();
            $this->_quote->save();

            $responseContent = [
                'success' => true,
                'redirect_url' => $this->_getServiceURL(),
                'vps_protocol' => $this->_config->getVPSProtocol(),
                'tx_type' => $this->_config->getSagepayPaymentAction(),
                'vendor' => $this->_config->getVendorname(),
                'crypt' => $this->_generateFormCrypt()
            ];

        }  catch (\Exception $e) {
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $e->getMessage()),
            ];
            $this->messageManager->addError(__('Something went wrong: ' . $e->getMessage()));
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    protected function _generateFormCrypt(){

        $encrypted_password = $this->_config->getFormEncryptedPassword();

        if(empty($encrypted_password)){
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid FORM encrypted password.'));
        }

        //$customer_data = $this->_getCustomerSession()->getCustomerDataObject();

        $data = array();
        $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());
        $data['Description'] = $this->_requestHelper->getOrderDescription();

        //referrer id
        $data["ReferrerID"] = $this->_requestHelper->getReferrerId();

        if($this->_config->getBasketFormat() != Config::BASKETFORMAT_Disabled) {
            $data = array_merge($data, $this->_requestHelper->populateBasketInformation($this->_quote));
        }

        $data['SuccessURL'] = $this->_url->getUrl('*/*/success');
        $data['FailureURL'] = $this->_url->getUrl('*/*/failure');

        //email details
        $data['VendorEMail'] = $this->_config->getFormVendorEmail();
        $data['SendEMail'] = $this->_config->getFormSendEmail();
        $data['EmailMessage'] = substr($this->_config->getFormEmailMessage(), 0, 7500);

        //populate payment amount information
        $data = array_merge($data, $this->_requestHelper->populatePaymentAmount($this->_quote));

        //populate address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

        //3D rules
        $data["Apply3DSecure"] = $this->_config->get3Dsecure();

        //Avs/Cvc rules
        $data["ApplyAVSCV2"] = $this->_config->getAvsCvc();

        //gif aid
        $data["AllowGiftAid"] = (int)$this->_config->isGiftAidEnabled();

//        $data['CustomerXML']  = $this->getConfigData('avscv2');
//        $data['SurchargeXML']  = $this->getConfigData('avscv2');
//        $data['VendorData']  = $this->getConfigData('avscv2');
//        $data['ReferrerID']        = $this->getConfigData('referrer_id');
//        $data['Website']        = $this->getConfigData('referrer_id');

        //log request
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $data);

        $preCryptString = '';
        foreach ($data as $field => $value) {
            if ($value != '') {
                $preCryptString .= ($preCryptString == '') ? "$field=$value" : "&$field=$value";
            }
        }

        $encryptor = new \Crypt_AES(CRYPT_AES_MODE_CBC);
        $encryptor->setBlockLength(128);
        $encryptor->setKey($encrypted_password);
        $encryptor->setIV($encrypted_password);
        $crypt = $encryptor->encrypt($preCryptString);

        return "@" . bin2hex($crypt);
    }

    protected function _getServiceURL(){
        if($this->_config->getMode()== \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE){
            return \Ebizmarts\SagePaySuite\Model\Config::URL_FORM_REDIRECT_LIVE;
        }else{
            return \Ebizmarts\SagePaySuite\Model\Config::URL_FORM_REDIRECT_TEST;
        }
    }
}
