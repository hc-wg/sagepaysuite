<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\Repeat;

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
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    protected $_checkoutHelper;

    /**
     *  POST array
     */
    protected $_postData;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_quoteSession;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * Sage Pay Suite Request Helper
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    protected $_requestHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared
     */
    protected $_sharedApi;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param Config $config
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     * @param Logger $suiteLogger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Ebizmarts\SagePaySuite\Helper\Request $requestHelper
     * @param \Ebizmarts\SagePaySuite\Model\Api\Shared $sharedApi
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        Logger $suiteLogger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Ebizmarts\SagePaySuite\Model\Api\Shared $sharedApi,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_REPEAT);
        $this->_suiteHelper = $suiteHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_sharedApi = $sharedApi;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_customerSession = $customerSession;
        $this->_quoteSession = $quoteSession;
        $this->_quoteManagement = $quoteManagement;
        $this->_requestHelper = $requestHelper;
        $this->_transactionFactory = $transactionFactory;
        $this->_quote = $this->_quoteSession->getQuote();
    }

    public function execute()
    {
        try {
            //parse POST data
            $this->_postData = $this->getRequest()->getPost();

            //prepare quote
            $this->_quote->collectTotals();
            $this->_quote->reserveOrderId();
            $vendorTxCode = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());

            //generate request data
            $request = $this->_generateRequest($vendorTxCode);

            //send REPEAT POST to Sage Pay
            $post_response = $this->_sharedApi->repeatTransaction(
                $this->_postData->vpstxid,
                $request,
                $this->_config->getSagepayPaymentAction());

            //set payment info for save order
            $transactionId = str_replace("{", "", str_replace("}", "", $post_response["data"]["VPSTxId"])); //strip brackets
            $payment = $this->_quote->getPayment();
            $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_REPEAT);
            $payment->setTransactionId($transactionId);
            $payment->setAdditionalInformation('statusDetail', $post_response["data"]["StatusDetail"]);
            $payment->setAdditionalInformation('vendorTxCode', $vendorTxCode);
            $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());
            $payment->setAdditionalInformation('moto', true);
            $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
            $payment->setAdditionalInformation('mode', $this->_config->getMode());

            //save order
            $order = $this->_quoteManagement->submit($this->_quote);

            if ($order) {

                //mark order as paid
                $this->_confirmPayment($transactionId,$order);

                //add success url to response
                $route = 'sales/order/view';
                $param['order_id'] = $order->getId();
                $url = $this->_backendUrl->getUrl($route, $param);
                $post_response["data"]["redirect"] = $url;

                //prepare response
                $responseContent = [
                    'success' => true,
                    'response' => $post_response
                ];

            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order.'));
            }

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_suiteLogger->logException($apiException);
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $apiException->getUserMessage()),
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

    protected function _generateRequest($vendorTxCode)
    {
        $data = array();

        $data['VendorTxCode'] = $vendorTxCode;
        $data['Description'] = $this->_requestHelper->getOrderDescription(true);

        //basket XML
//        if($this->_config->isSendBasket()) {
//            $data = array_merge($data, $this->_requestHelper->populateBasketInformation($this->_quote,true));
//        }

        //populate payment amount information
        $data = array_merge($data, $this->_requestHelper->populatePaymentAmount($this->_quote));

        //populate address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

        return $data;
    }

    protected function _confirmPayment($transactionId,$order)
    {
        $payment = $order->getPayment();
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);

        //leave transaction open in case defer
        if($this->_config->getSagepayPaymentAction() == Config::ACTION_REPEAT_DEFERRED){
            $payment->setIsTransactionClosed(0);
        }

        $payment->save();
        $payment->getMethodInstance()->markAsInitialized();
        $order->place()->save();

        //send email
        $this->_checkoutHelper->sendOrderEmail($order);
    }
}
