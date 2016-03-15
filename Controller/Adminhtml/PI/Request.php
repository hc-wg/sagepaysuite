<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\PI;

use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Api\PIRest;

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
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRest
     */
    protected $_pirestapi;

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
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     * @param Logger $suiteLogger
     * @param PIRest $pirestapi
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        Logger $suiteLogger,
        PIRest $pirestapi,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_suiteHelper = $suiteHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_pirestapi = $pirestapi;
        $this->_logger = $logger;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteManagement = $quoteManagement;
        $this->_quote = $this->_checkoutSession->getQuote();
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

            //generate POST request
            $request = $this->_generateRequest($vendorTxCode);

            //send POST to Sage Pay
            $post_response = $this->_pirestapi->capture($request);

            if ($post_response->statusCode == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {

                //set payment info for save order
                $transactionId = $post_response->transactionId;
                $payment = $this->_quote->getPayment();
                $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
                $payment->setTransactionId($transactionId);
                $payment->setCcLast4($this->_postData->card_last4);
                $payment->setCcExpMonth($this->_postData->card_exp_month);
                $payment->setCcExpYear($this->_postData->card_exp_year);
                $payment->setCcType($this->_postData->card_type);
                $payment->setAdditionalInformation('statusCode', $post_response->statusCode);
                $payment->setAdditionalInformation('statusDetail', $post_response->statusDetail);
                $payment->setAdditionalInformation('vendorTxCode', $vendorTxCode);
                if (isset($post_response->{'3DSecure'})) {
                    $payment->setAdditionalInformation('threeDStatus', $post_response->{'3DSecure'}->status);
                }
                $payment->setAdditionalInformation('moto', true);
                $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
                $payment->setAdditionalInformation('mode', $this->_config->getMode());

                //save order with pending payment
                $order = $this->_quoteManagement->submit($this->_quote);

                if ($order) {
                    $payment = $order->getPayment();
                    $payment->setTransactionId($transactionId);
                    $payment->setLastTransId($transactionId);
                    $payment->save();

                    $payment->getMethodInstance()->markAsInitialized();
                    $order->place()->save();

                    //send email
                    $this->_checkoutHelper->sendOrderEmail($order);

                    //add success url to response
                    $route = 'sales/order/view';
                    $param['order_id'] = $order->getId();
                    $url = $this->_backendUrl->getUrl($route, $param);
                    $post_response->redirect = $url;

                    //prepare response
                    $responseContent = [
                        'success' => true,
                        'response' => $post_response
                    ];

                } else {
                    throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order.'));
                }

            } else {
                throw new \Magento\Framework\Validator\Exception(__('Invalid Sage Pay response.'));
            }

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $apiException->getUserMessage()),
            ];

        } catch (\Exception $e) {
            $this->_logger->critical($e);
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

        $billing_address = $this->_quote->getBillingAddress();

        $data = [
            'transactionType' => $this->_config->getSagepayPaymentAction(),
            'paymentMethod' => [
                'card' => [
                    'merchantSessionKey' => $this->_postData->merchant_session_Key,
                    'cardIdentifier' => $this->_postData->card_identifier,
                ]
            ],
            'vendorTxCode' => $vendorTxCode,
            'amount' => $this->_quote->getGrandTotal() * 100,
            'currency' => $this->_quote->getQuoteCurrencyCode(),
            'description' => "Magento MOTO transaction.",
            'customerFirstName' => $billing_address->getFirstname(),
            'customerLastName' => $billing_address->getLastname(),
            'billingAddress' => [
                'address1' => $billing_address->getStreetLine(1),
                'city' => $billing_address->getCity(),
                'postalCode' => $billing_address->getPostCode(),
                'country' => $billing_address->getCountryId()
            ],
            'entryMethod' => "Ecommerce",
            'apply3DSecure' => 'Disable'
        ];

        if ($billing_address->getCountryId() == "US") {
            $data["billingAddress"]["state"] = substr($billing_address->getRegionCode(), 0, 2);
        }

        return $data;
    }
}
