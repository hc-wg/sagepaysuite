<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Paypal;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Request extends \Magento\Framework\App\Action\Action
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
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post
     */
    private $_postApi;

    /**
     * Sage Pay Suite Request Helper
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    private $_requestHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Api\Post $postApi,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession
    ) {
    
        parent::__construct($context);
        $this->_config          = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);
        $this->_suiteHelper     = $suiteHelper;
        $this->_suiteLogger     = $suiteLogger;
        $this->_postApi         = $postApi;
        $this->_requestHelper   = $requestHelper;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_quote           = $this->_checkoutSession->getQuote();
    }

    public function execute()
    {
        try {
            $this->_quote->collectTotals();
            $this->_quote->reserveOrderId();
            $this->_quote->save();

            //generate POST request
            $request = $this->_generateRequest();

            //send POST to Sage Pay
            $post_response = $this->_postApi->sendPost(
                $request,
                $this->_getServiceURL(),
                ["PPREDIRECT"],
                'Invalid response from PayPal'
            );

            //prepare response
            $responseContent = [
                'success' => true,
                'response' => $post_response
            ];
        } catch (\Exception $e) {
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

    private function _getCallbackUrl()
    {
        $url = $this->_url->getUrl('*/*/processing', [
            '_secure' => true,
            '_store' => $this->_quote->getStoreId()
        ]);

        $url .= "?quoteid=" . $this->_quote->getId();

        return $url;
    }

    private function _getServiceURL()
    {
        if ($this->_config->getMode()== \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_DIRECT_POST_LIVE;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_DIRECT_POST_TEST;
        }
    }

    /**
     * return array
     */
    private function _generateRequest()
    {
        $data = [];
        $data["VPSProtocol"] = $this->_config->getVPSProtocol();
        $data["TxType"] = $this->_config->getSagepayPaymentAction();
        $data["Vendor"] = $this->_config->getVendorname();
        $data["VendorTxCode"] = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());
        $data["Description"] = __("Store transaction");

        //referrer id
        $data["ReferrerID"] = $this->_requestHelper->getReferrerId();

        if ($this->_config->getBasketFormat() != Config::BASKETFORMAT_DISABLED) {
            $forceXmlBasket = $this->_config->isPaypalForceXml();

            $basket = $this->_requestHelper->populateBasketInformation($this->_quote, $forceXmlBasket);
            $data   = array_merge($data, $basket);
        }

        $data["CardType"] = "PAYPAL";

        //populate payment amount information
        $data = array_merge($data, $this->_requestHelper->populatePaymentAmount($this->_quote));

        $data = $this->_requestHelper->unsetBasketXMLIfAmountsDontMatch($data);

        //address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

        $data["PayPalCallbackURL"] = $this->_getCallbackUrl();
        $data["BillingAgreement"] = (int)$this->_config->getPaypalBillingAgreement();

        return $data;
    }
}
