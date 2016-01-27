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
     * @var \Ebizmarts\SagePaySuite\Model\Server
     */
    protected $_serverModel;

    /**
     *  POST array
     */
    protected $_postData;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        Logger $suiteLogger,
        \Psr\Log\LoggerInterface $logger,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Model\Server $serverModel
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
        $this->_suiteHelper = $suiteHelper;
        $this->_curlFactory = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;
        $this->_quote = $this->_getCheckoutSession()->getQuote();
        $this->_quoteManagement = $quoteManagement;
        $this->_suiteLogger = $suiteLogger;
        $this->_logger = $logger;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_serverModel = $serverModel;

        $postData = $this->getRequest();
        $postData = preg_split('/^\r?$/m', $postData, 2);
        $postData = json_decode(trim($postData[1]));
        $this->_postData = $postData;
    }

    public function execute()
    {
        try {

            //prepare quote
            $this->_quote->collectTotals();
            $this->_quote->reserveOrderId();

            //generate POST request
            $request = $this->_serverModel->generateRequest($this->_quote,
                $this->_getCustomerSession()->getCustomerDataObject(),
                $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId()),
                $this->_getNotificationUrl(),
                $this->_postData->save_token,
                $this->_postData->token
            );

            //send POST to Sage Pay
            $post_response = $this->_handleApiErrors($this->_sendPost($request));

            //set payment info for save order
            $transactionId = $post_response["data"]["VPSTxId"];
            $transactionId = str_replace("}","",str_replace("{","",$transactionId));
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

    protected function _getNotificationUrl()
    {
        $url = $this->_url->getUrl('*/*/notify', array(
            '_secure' => true,
            '_store' => $this->_quote->getStoreId()
        ));

        $url .= "?quoteid=" . $this->_quote->getId();

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

        $response_data = [];
        if($response_status == 200){

            //parse response
            $data = preg_split('/^\r?$/m', $data, 2);
            $data = explode(chr(13), $data[1]);

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
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
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
}
