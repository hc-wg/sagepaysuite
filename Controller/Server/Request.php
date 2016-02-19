<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Server;


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
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var string
     */
    protected $_assignedVendorTxCode;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    protected $_checkoutHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post
     */
    protected $_postApi;

    /**
     *  POST array
     */
    protected $_postData;

    /**
     * Sage Pay Suite Request Helper
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    protected $_requestHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Token
     */
    protected $_tokenModel;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Api\Post $postApi,
        Logger $suiteLogger,
        \Psr\Log\LoggerInterface $logger,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Ebizmarts\SagePaySuite\Model\Token $tokenModel,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
        $this->_suiteHelper = $suiteHelper;
        $this->_postApi = $postApi;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_quote = $this->_checkoutSession->getQuote();
        $this->_suiteLogger = $suiteLogger;
        $this->_logger = $logger;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_requestHelper = $requestHelper;
        $this->_tokenModel = $tokenModel;
    }

    public function execute()
    {
        try {

            //parse POST data
            $postData = $this->getRequest();
            $postData = preg_split('/^\r?$/m', $postData, 2);
            $postData = json_decode(trim($postData[1]));
            $this->_postData = $postData;

            //prepare quote
            $this->_quote->collectTotals();
            $this->_quote->reserveOrderId();

            //generate POST request
            $request = $this->_generateRequest();

            //send POST to Sage Pay
            $post_response = $this->_postApi->sendPost($request,
                $this->_getServiceURL(),
                array("OK")
            );

            //set payment info for save order
            $transactionId = $post_response["data"]["VPSTxId"];
            $transactionId = str_replace("}", "", str_replace("{", "", $transactionId));
            $payment = $this->_quote->getPayment();
            $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);

            //save order with pending payment
            $order = $this->_checkoutHelper->placeOrder();

            //set payment data
            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setAdditionalInformation('vendorTxCode', $this->_assignedVendorTxCode);
            $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
            $payment->setAdditionalInformation('mode', $this->_config->getMode());
            $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());
            $payment->save();

            //prepare response
            $responseContent = [
                'success' => true,
                'response' => $post_response
            ];

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {

            $this->_logger->critical($apiException);

            echo $apiException->getUserMessage();

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong while generating the Sage Pay request: ' . $apiException->getUserMessage()),
            ];
        } catch (\Exception $e) {

            $this->_logger->critical($e);

            echo $e->getMessage();

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong while generating the Sage Pay request: ' . $e->getMessage()),
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    protected function _getNotificationUrl()
    {
        $url = $this->_url->getUrl('*/*/notify', array(
            '_secure' => true,
            '_store' => $this->_quote->getStoreId()
        ));

        $url .= "?quoteid=" . $this->_quote->getId();

        return $url;
    }

    /**
     * @return string
     */
    protected function _getServiceURL()
    {
        if ($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_LIVE;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_TEST;
        }
    }

    /**
     * return array
     */
    protected function _generateRequest()
    {
        $data = array();
        $data["VPSProtocol"] = $this->_config->getVPSProtocol();
        $data["TxType"] = $this->_config->getSagepayPaymentAction();
        $data["Vendor"] = $this->_config->getVendorname();
        $data["VendorTxCode"] = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());
        $data["Amount"] = number_format($this->_quote->getGrandTotal(), 2, '.', '');
        $data["Currency"] = $this->_quote->getQuoteCurrencyCode();
        $data["Description"] = "Magento transaction";
        $data["NotificationURL"] = $this->_getNotificationUrl();

        //address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

        //token
        if ($this->_postData->save_token == true &&
            !empty($this->_customerSession->getCustomerDataObject()) &&
            !$this->_tokenModel->isCustomerUsingMaxTokenSlots(
                $this->_customerSession->getCustomerDataObject()->getId(),
                $this->_config->getVendorname()
            )
        ) {
            //save token
            $data["CreateToken"] = 1;
        } else {
            if (!is_null($this->_postData->token)) {
                //use token
                $data["StoreToken"] = 1;
                $data["Token"] = $this->_postData->token;
            }
        }

        //not mandatory
//        BillingAddress2
//        BillingPhone
//        DeliveryAddress2
//        DeliveryPhone
//        CustomerEMail
//        Basket
//        AllowGiftAid
//        ApplyAVSCV2
//        Apply3DSecure
//        Profile
//        BillingAgreement
//        AccountType
//        BasketXML
//        CustomerXML
//        SurchargeXML
//        VendorData
//        ReferrerID
//        Language
//        Website
//        FIRecipientAcctNumber
//        FIRecipientSurname
//        FIRecipientPostcode
//        FIRecipientDoB

        return $data;
    }
}
