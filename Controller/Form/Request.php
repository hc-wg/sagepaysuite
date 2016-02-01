<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

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
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->_suiteHelper = $suiteHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_requestHelper = $requestHelper;

        $this->_quote = $this->_getCheckoutSession()->getQuote();
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
                'error_message' => __('Something went wrong while generating the Sage Pay form request: ' . $e->getMessage()),
            ];
            $this->messageManager->addError(__('Something went wrong while generating the Sage Pay form request: ' . $e->getMessage()));
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    private function _generateFormCrypt(){

        $encrypted_password = $this->_config->getFormEncryptedPassword();

        if(empty($encrypted_password)){
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid FORM encrypted password.'));
        }

        $customer_data = $this->_getCustomerSession()->getCustomerDataObject();

        $data = array();
        $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());
        $data['Amount'] = number_format($this->_quote->getGrandTotal(), 2, '.', '');
        $data['Currency'] = $this->_quote->getQuoteCurrencyCode();
        $data['Description'] = "Magento transaction";
        $data['SuccessURL'] = $this->_url->getUrl('*/*/success');
        $data['FailureURL'] = $this->_url->getUrl('*/*/failure');

        //not mandatory
//        $data['CustomerName'] = $billing_address->getFirstname() . ' ' . $billing_address->getLastname();
//        $data['CustomerEMail'] = ($customerEmail == null ? $billing->getEmail() : $customerEmail);
//        $data['VendorEMail']
//        $data['SendEMail']
//        $data['EmailMessage']

        //populate address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

//        $data['BasketXML'] = $basket;
//        $data['AllowGiftAid'] = (int)$this->getConfigData('allow_gift_aid');
//        $data['ApplyAVSCV2']  = $this->getConfigData('avscv2');
//        $data['Apply3DSecure']  = $this->getConfigData('avscv2');
//        $data['BillingAgreement']  = $this->getConfigData('avscv2');
//        $data['BasketXML']  = $this->getConfigData('avscv2');
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

        ksort($data);

        //** add PKCS5 padding to the text to be encypted
        $pkcs5Data = $this->_addPKCS5Padding($preCryptString);

        $crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $encrypted_password, $pkcs5Data, MCRYPT_MODE_CBC, $encrypted_password);

        return "@" . bin2hex($crypt);
    }

    private function _getServiceURL(){
        if($this->_config->getMode()== \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE){
            return \Ebizmarts\SagePaySuite\Model\Config::URL_FORM_REDIRECT_LIVE;
        }else{
            return \Ebizmarts\SagePaySuite\Model\Config::URL_FORM_REDIRECT_TEST;
        }
    }

    //** PHP's mcrypt does not have built in PKCS5 Padding, so we use this
    protected function _addPKCS5Padding($input) {
        $blocksize = 16;
        $padding = "";

        // Pad input to an even block size boundary
        $padlength = $blocksize - (strlen($input) % $blocksize);
        for ($i = 1; $i <= $padlength; $i++) {
            $padding .= chr($padlength);
        }

        return $input . $padding;
    }

    protected function _getCheckoutSession()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCustomerSession()
    {
        return $this->_objectManager->get('Magento\Customer\Model\Session');
    }
}
