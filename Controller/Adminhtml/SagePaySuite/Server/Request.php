<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\SagePaySuite\Server;


use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey;

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
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     *
     */
    protected $_curlFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory
     */
    protected $_apiExceptionFactory;

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
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * Adminhtml data
     *
     * @var \Magento\Backend\Helper\Data
     */
    protected $_adminhtmlData;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Product $productHelper
     * @param Escaper $escaper
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param DataHelper $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        Logger $suiteLogger,
        \Psr\Log\LoggerInterface $logger,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\Data\Form\FormKey $formKey
    )
    {
        parent::__construct($context);
        $this->formKey = $formKey;
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
        $this->_suiteHelper = $suiteHelper;
        $this->_curlFactory = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;
        $this->_quote = $this->_getSession()->getQuote();
        $this->_quoteManagement = $quoteManagement;
        $this->_suiteLogger = $suiteLogger;
        $this->_logger = $logger;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_quoteManagement = $quoteManagement;
    }

    /**
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function execute()
    {
        try {

            //prepare quote
            $this->_quote->collectTotals();
            $this->_quote->reserveOrderId();

            //generate POST request
            $request = $this->_generateRequest();

            //send POST to Sage Pay
            $post_response = $this->_handleApiErrors($this->_sendPost($request));

            //set payment info for save order
            $transactionId = $post_response["data"]["VPSTxId"];
            $transactionId = str_replace("}", "", str_replace("{", "", $transactionId));
            $payment = $this->_quote->getPayment();
            $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);

            //save order with pending payment
            //$order = $this->_checkoutHelper->placeOrder();
            $order = $this->_quoteManagement->submit($this->_quote);

            if (!$order) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Can not save order. Please try another payment option.'));
            }

            //set payment data
            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setAdditionalInformation('vendorTxCode', $this->_assignedVendorTxCode);
            $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
            $payment->setAdditionalInformation('mode', $this->_config->getMode());
            $payment->save();

            //prepare response
            $responseContent = [
                'success' => true,
                'response' => $post_response
            ];

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {

            $this->_logger->critical($apiException);

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong while generating the Sage Pay request: ' . $apiException->getUserMessage()),
            ];
            //$this->messageManager->addError(__('Something went wrong while generating the Sage Pay request: ' . $apiException->getUserMessage()));

        } catch (\Exception $e) {

            $this->_logger->critical($e);

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong while generating the Sage Pay request: ' . $e->getMessage()),
            ];
            //$this->messageManager->addError(__('Something went wrong while generating the Sage Pay request: ' . $e->getMessage()));
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    private function _generateRequest(){

        $billing_address = $this->_quote->getBillingAddress();
        $shipping_address = $this->_quote->getShippingAddress();
        $customer_data = $this->_getCustomerSession()->getCustomerDataObject();
        $this->_assignedVendorTxCode = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());

        $post_data = array();
        $post_data["VPSProtocol"] = $this->_config->getVPSProtocol();
        $post_data["TxType"] = $this->_config->getSagepayPaymentAction();
        $post_data["Vendor"] = $this->_config->getVendorname();
        $post_data["VendorTxCode"] = $this->_assignedVendorTxCode;
        $post_data["Amount"] = number_format($this->_quote->getGrandTotal(), 2, '.', '');
        if($this->_config->isSendBasket()) {
            $post_data = array_merge($post_data, $this->_requestHelper->populateBasketInformation($this->_quote));
        }
        if($this->_config->getSendBasket()) {
            $post_data = array_merge($post_data, $this->_requestHelper->populateBasketInformation($this->_quote));
        }
        $post_data["Currency"] = $this->_quote->getQuoteCurrencyCode();
        $post_data["Description"] = "Magento transaction";
        $post_data["NotificationURL"] = $this->_getNotificationUrl();
        $post_data["BillingSurname"] = substr($billing_address->getLastname(), 0, 20);
        $post_data["BillingFirstnames"] = substr($billing_address->getFirstname(), 0, 20);
        $post_data["BillingAddress1"] = substr($billing_address->getStreetLine(1), 0, 100);
        $post_data["BillingCity"] = substr($billing_address->getCity(), 0,  40);
        $post_data["BillingState"] = substr($billing_address->getRegionCode(), 0, 2);
        $post_data["BillingPostCode"] = substr($billing_address->getPostcode(), 0, 10);
        $post_data["BillingCountry"] = substr($billing_address->getCountryId(), 0, 2);
        $post_data["DeliverySurname"] = substr($shipping_address->getLastname(), 0, 20);
        $post_data["DeliveryFirstnames"] = substr($shipping_address->getFirstname(), 0, 20);
        $post_data["DeliveryAddress1"] = substr($shipping_address->getStreetLine(1), 0, 100);
        $post_data["DeliveryCity"] = substr($shipping_address->getCity(), 0,  40);
        $post_data["DeliveryState"] = substr($shipping_address->getRegionCode(), 0, 2);
        $post_data["DeliveryPostCode"] = substr($shipping_address->getPostcode(), 0, 10);
        $post_data["DeliveryCountry"] = substr($shipping_address->getCountryId(), 0, 2);

        //not mandatory
//        Token
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
//        CreateToken
//        StoreToken
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

        return $post_data;
    }

    protected function _getNotificationUrl()
    {


        $url = $this->_url->getUrl('*/*/notify', array(
            '_secure' => true,
            '_store' => $this->_quote->getStoreId()
        ));

        $url .= "?quoteid=" . $this->_quote->getId();
        $url .= "&form_key=" . $this->formKey->getFormKey();

        return $url;
    }

    protected function _sendPost ($postData){

        $curl = $this->_curlFactory->create();
        $url = $this->_getServiceURL();

        $post_data_string = '';
        foreach ($postData as $_key => $_val) {
            $post_data_string .= $_key . '=' . urlencode(mb_convert_encoding($_val, 'ISO-8859-1', 'UTF-8')) . '&';
        }

        //log SERVER request
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST,$postData);

        $curl->setConfig(
            [
                'timeout' => 120,
                'verifypeer' => false,
                'verifyhost' => 2
            ]
        );

        $curl->write(\Zend_Http_Client::POST,
            $url,
            '1.0',
            [],
            $post_data_string);
        $data = $curl->read();

        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
        $curl->close();

        //log SERVER response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST,$data);

        if($response_status == 200){

            //parse response
            $data = preg_split('/^\r?$/m', $data, 2);
            $data = explode('\n', $data[1]);
            $response_data = [];
            for($i=0;$i<count($data);$i++){
                if(!empty($data[$i])){
                    $aux = explode("=",trim($data[$i]));
                    if(count($aux) == 2){
                        $response_data[$aux[0]] = $aux[1];
                    }else{
                        if(count($aux) > 2){
                            $response_data[$aux[0]] = $aux[1];
                            for($j=2;$j<count($aux);$j++){
                                $response_data[$aux[0]] .= "=" . $aux[$j];
                            }
                        }
                    }
                }
            }
        }

        $response = [
            "status" => $response_status,
            "data" => $response_data
        ];

        return $response;
    }

    /**
     * @return string
     */
    private function _getServiceURL(){
        if($this->_config->getMode()== \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE){
            return \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_LIVE;
        }else{
            return \Ebizmarts\SagePaySuite\Model\Config::URL_SERVER_POST_TEST;
        }
    }

    /**
     * @return \Magento\Backend\Model\Session\Quote
     */
    protected function _getSession()
    {
        return $this->_objectManager->get('Magento\Backend\Model\Session\Quote');
    }

    protected function _getCustomerSession()
    {
        return $this->_objectManager->get('Magento\Customer\Model\Session');
    }

    protected function _handleApiErrors($response)
    {
        $exceptionPhrase = "Invalid response from Sage Pay";
        $exceptionCode = 0;

        if($response["status"] == 200){

            if (!empty($response) && array_key_exists("data",$response)) {
                if(array_key_exists("Status",$response["data"]) && $response["data"]["Status"] == 'OK'){

                    //this is a successfull response
                    return $response;

                }else{

                    //there was an error
                    $detail = explode(":",$response["data"]["StatusDetail"]);
                    $exceptionCode = trim($detail[0]);
                    $exceptionPhrase = trim($detail[1]);
                }
            }
        }

        $exception = $this->_apiExceptionFactory->create([
            'phrase' => __($exceptionPhrase),
            'code' => $exceptionCode
        ]);
        throw $exception;
    }

    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        //return $this->_authorization->isAllowed('Ebizmarts_SagePaySuite::resource');
        return true;

    }
}
