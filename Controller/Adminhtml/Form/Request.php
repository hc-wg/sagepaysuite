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
    private $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * Sage Pay Suite Request Helper
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    private $_requestHelper;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $_quoteSession;

    private $formCrypt;

    /**
     * Request constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param Config $config
     * @param Logger $suiteLogger
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     * @param \Ebizmarts\SagePaySuite\Helper\Request $requestHelper
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Ebizmarts\SagePaySuite\Model\FormCrypt $formCrypt
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Ebizmarts\SagePaySuite\Model\FormCrypt $formCrypt
    ) {
    
        parent::__construct($context);
        $this->_config        = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->_suiteHelper   = $suiteHelper;
        $this->_suiteLogger   = $suiteLogger;
        $this->_requestHelper = $requestHelper;
        $this->_quoteSession  = $quoteSession;
        $this->_quote         = $this->_quoteSession->getQuote();
        $this->formCrypt      = $formCrypt;
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
            $this->_suiteLogger->logException($e, [__METHOD__, __LINE__]);

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: %1', $e->getMessage()),
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    private function _generateFormCrypt()
    {

        $encryptedPassword = $this->_config->getFormEncryptedPassword();

        if (empty($encryptedPassword)) {
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
        $data = array_merge($data, $this->_requestHelper->populatePaymentAmountAndCurrency($this->_quote));

        $data = $this->_requestHelper->unsetBasketXMLIfAmountsDontMatch($data);

        //populate address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

        $data["CardHolder"]    = $data['BillingFirstnames'] . ' ' . $data['BillingSurname'];

        //3D rules
        $data["Apply3DSecure"] = $this->_config->get3Dsecure(true);

        //Avs/Cvc rules
        $data["ApplyAVSCV2"] = $this->_config->getAvsCvc();

        //gif aid
        $data["AllowGiftAid"] = (int)$this->_config->isGiftAidEnabled();

        //log request
        $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $data, [__METHOD__, __LINE__]);

        $preCryptString = '';
        foreach ($data as $field => $value) {
            if ($value != '') {
                $preCryptString .= ($preCryptString == '') ? "$field=$value" : "&$field=$value";
            }
        }

        return $this->getEncryptedRequest($encryptedPassword, $preCryptString);
    }

    private function _getServiceURL()
    {
        if ($this->_config->getMode()== \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_FORM_REDIRECT_LIVE;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_FORM_REDIRECT_TEST;
        }
    }

    /**
     * @param $encryptedPassword
     * @param $preCryptString
     * @return string
     */
    private function getEncryptedRequest($encryptedPassword, $preCryptString)
    {
        $this->formCrypt->initInitializationVectorAndKey($encryptedPassword);

        $crypt = $this->formCrypt->encrypt($preCryptString);

        return $crypt;
    }
}
