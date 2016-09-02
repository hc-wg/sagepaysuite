<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\Form;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Request extends \Magento\Backend\App\AbstractAction
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
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_quoteSession;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param Logger $suiteLogger
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     * @param \Ebizmarts\SagePaySuite\Helper\Request $requestHelper
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Magento\Backend\Model\Session\Quote $quoteSession
    ) {
    
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->_suiteHelper = $suiteHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_requestHelper = $requestHelper;
        $this->_quoteSession = $quoteSession;
        $this->_quote = $this->_quoteSession->getQuote();
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
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e);

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $e->getMessage()),
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    protected function _generateFormCrypt()
    {

        $encrypted_password = $this->_config->getFormEncryptedPassword();

        if (empty($encrypted_password)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid FORM encrypted password.'));
        }

        $data = [];
        $data['VendorTxCode'] = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());
        $data['Description'] = $this->_requestHelper->getOrderDescription();

        //referrer id
        $data["ReferrerID"] = $this->_requestHelper->getReferrerId();

        if ($this->_config->getBasketFormat() != Config::BASKETFORMAT_DISABLED) {
            $data = array_merge($data, $this->_requestHelper->populateBasketInformation($this->_quote));
        }

        $data['SuccessURL'] = $this->_backendUrl->getUrl('*/*/success');
        $data['FailureURL'] = $this->_backendUrl->getUrl('*/*/failure');

        //email details
        $data['VendorEMail']  = $this->_config->getFormVendorEmail();
        $data['SendEMail']    = $this->_config->getFormSendEmail();
        $data['EmailMessage'] = substr($this->_config->getFormEmailMessage(), 0, 7500);

        //populate payment amount information
        $data = array_merge($data, $this->_requestHelper->populatePaymentAmount($this->_quote));

        //populate address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

        $data["CardHolder"]    = $data['BillingFirstnames'] . ' ' . $data['BillingSurname'];

        //3D rules
        $data["Apply3DSecure"] = $this->_config->get3Dsecure(true);

        //Avs/Cvc rules
        $data["ApplyAVSCV2"] = $this->_config->getAvsCvc();

        //gif aid
        $data["AllowGiftAid"] = (int)$this->_config->isGiftAidEnabled();

//        $data['CustomerXML']  = $this->getConfigData('avscv2');
//        $data['SurchargeXML']  = $this->getConfigData('avscv2');
//        $data['VendorData']  = $this->getConfigData('avscv2');
//        $data['Website']        = $this->getConfigData('referrer_id');

        //log request
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $data);

        $preCryptString = '';
        foreach ($data as $field => $value) {
            if ($value != '') {
                $preCryptString .= ($preCryptString == '') ? "$field=$value" : "&$field=$value";
            }
        }

        $encryptor = new \phpseclib\Crypt\AES(\phpseclib\Crypt\Base::MODE_CBC);
        $encryptor->setBlockLength(128);
        $encryptor->setKey($encrypted_password);
        $encryptor->setIV($encrypted_password);
        $crypt = $encryptor->encrypt($preCryptString);

        return "@" . strtoupper(bin2hex($crypt));
    }

    protected function _getServiceURL()
    {
        if ($this->_config->getMode()== \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_FORM_REDIRECT_LIVE;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_FORM_REDIRECT_TEST;
        }
    }
}
