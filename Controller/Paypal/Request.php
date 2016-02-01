<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Paypal;

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
     * @var \Ebizmarts\SagePaySuite\Model\Api\Post
     */
    protected $_postApi;

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
        \Ebizmarts\SagePaySuite\Model\Api\Post $postApi,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);
        $this->_suiteHelper = $suiteHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_postApi = $postApi;
        $this->_requestHelper = $requestHelper;

        $this->_quote = $this->_getCheckoutSession()->getQuote();
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
            $post_response = $this->_postApi->sendPost($request,
                $this->_getServiceURL(),
                array("PPREDIRECT"),
                'Invalid response from PayPal'
            );
            //$post_response = $this->_handleApiErrors($this->_sendPost($request));

            //prepare response
            $responseContent = [
                'success' => true,
                'response' => $post_response
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

    protected function _getCallbackUrl()
    {
        $url = $this->_url->getUrl('*/*/callback', array(
            '_secure' => true,
            '_store' => $this->_quote->getStoreId()
        ));

        $url .= "?quoteid=" . $this->_quote->getId();

        return $url;
    }

    private function _getServiceURL(){
        if($this->_config->getMode()== \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE){
            return \Ebizmarts\SagePaySuite\Model\Config::URL_DIRECT_POST_LIVE;
        }else{
            return \Ebizmarts\SagePaySuite\Model\Config::URL_DIRECT_POST_TEST;
        }
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

//    protected function _handleApiErrors($response)
//    {
//        $exceptionPhrase = "Invalid response from Sage Pay";
//        $exceptionCode = 0;
//
//        if($response["status"] == 200){
//
//            if (!empty($response) && array_key_exists("data",$response)) {
//                if(array_key_exists("Status",$response["data"]) && $response["data"]["Status"] == 'PPREDIRECT'){
//
//                    //this is a successfull response
//                    return $response;
//
//                }else{
//
//                    //there was an error
//                    $detail = explode(":",$response["data"]["StatusDetail"]);
//                    $exceptionCode = trim($detail[0]);
//                    $exceptionPhrase = trim($detail[1]);
//                }
//            }
//        }
//
//        $exception = $this->_apiExceptionFactory->create([
//            'phrase' => __($exceptionPhrase),
//            'code' => $exceptionCode
//        ]);
//        throw $exception;
//    }
//
//    protected function _sendPost ($postData){
//
//        $curl = $this->_curlFactory->create();
//        $url = $this->_getServiceURL();
//
//        $post_data_string = '';
//        foreach ($postData as $_key => $_val) {
//            $post_data_string .= $_key . '=' . urlencode(mb_convert_encoding($_val, 'ISO-8859-1', 'UTF-8')) . '&';
//        }
//
//        //log request
//        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST,$postData);
//
//        $curl->setConfig(
//            [
//                'timeout' => 120,
//                'verifypeer' => false,
//                'verifyhost' => 2
//            ]
//        );
//
//        $curl->write(\Zend_Http_Client::POST,
//            $url,
//            '1.0',
//            [],
//            $post_data_string);
//        $data = $curl->read();
//
//        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
//        $curl->close();
//
//        //log response
//        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST,$data);
//
//        $response_data = [];
//        if($response_status == 200){
//
//            //parse response
//            $data = preg_split('/^\r?$/m', $data, 2);
//            $data = explode(chr(13), $data[1]);
//
//            for($i=0;$i<count($data);$i++){
//                if(!empty($data[$i])){
//                    $aux = explode("=",trim($data[$i]));
//                    if(count($aux) == 2){
//                        $response_data[$aux[0]] = $aux[1];
//                    }else{
//                        if(count($aux) > 2){
//                            $response_data[$aux[0]] = $aux[1];
//                            for($j=2;$j<count($aux);$j++){
//                                $response_data[$aux[0]] .= "=" . $aux[$j];
//                            }
//                        }
//                    }
//                }
//            }
//        }
//
//        $response = [
//            "status" => $response_status,
//            "data" => $response_data
//        ];
//
//        return $response;
//    }

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
        $data["CardType"] = "PAYPAL";

        //address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

//        $data["BillingSurname"] = substr($billing_address->getLastname(), 0, 20);
//        $data["BillingFirstnames"] = substr($billing_address->getFirstname(), 0, 20);
//        $data["BillingAddress1"] = substr($billing_address->getStreetLine(1), 0, 100);
//        $data["BillingCity"] = substr($billing_address->getCity(), 0,  40);
//        $data["BillingState"] = substr($billing_address->getRegionCode(), 0, 2);
//        $data["BillingPostCode"] = substr($billing_address->getPostcode(), 0, 10);
//        $data["BillingCountry"] = substr($billing_address->getCountryId(), 0, 2);
//        $data["DeliverySurname"] = substr($shipping_address->getLastname(), 0, 20);
//        $data["DeliveryFirstnames"] = substr($shipping_address->getFirstname(), 0, 20);
//        $data["DeliveryAddress1"] = substr($shipping_address->getStreetLine(1), 0, 100);
//        $data["DeliveryCity"] = substr($shipping_address->getCity(), 0,  40);
//        $data["DeliveryState"] = substr($shipping_address->getRegionCode(), 0, 2);
//        $data["DeliveryPostCode"] = substr($shipping_address->getPostcode(), 0, 10);
//        $data["DeliveryCountry"] = substr($shipping_address->getCountryId(), 0, 2);

        $data["PayPalCallbackURL"] = $this->_getCallbackUrl();
        $data["BillingAgreement"] = (int)$this->_config->getPaypalBillingAgreement();

        return $data;
    }
}
