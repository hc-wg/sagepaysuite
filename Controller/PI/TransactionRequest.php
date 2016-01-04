<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\PI;


use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Api\PIRestApi;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;


class TransactionRequest extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRestApi
     */
    protected $_pirestapi;

    protected $_postData;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        Logger $suiteLogger,
        PIRestApi $pirestapi,
        OrderSender $orderSender
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_suiteHelper = $suiteHelper;
        $this->_quote = $this->_getCheckoutSession()->getQuote();
        $this->_quoteManagement = $quoteManagement;
        $this->_suiteLogger = $suiteLogger;
        $this->_pirestapi = $pirestapi;

        $postData = $this->getRequest();
        $postData = preg_split('/^\r?$/m', $postData, 2);
        $postData = json_decode(trim($postData[1]));
        $this->_postData = $postData;
        $this->orderSender = $orderSender;
    }

    public function execute()
    {
        try {

            //prepare quote
            $this->_quote->collectTotals();
            $this->_quote->reserveOrderId();
            $vendorTxCode = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());

            //generate POST request
            $request = $this->_generateRequest($vendorTxCode);

            //send POST to Sage Pay
            $post_response = $this->_pirestapi->capture($request);

            if ($post_response->statusCode == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS ||
                $post_response->statusCode == \Ebizmarts\SagePaySuite\Model\Config::AUTH3D_REQUIRED_STATUS) {

                //set payment info for save order
                $transactionId = $post_response->transactionId;
                $payment = $this->_quote->getPayment();
                $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
                $payment->setTransactionId($transactionId);
                $payment->setAdditionalInformation('statusCode', $post_response->statusCode);
                //$payment->setAdditionalInformation('transactionType', $post_response->transactionType);
                $payment->setAdditionalInformation('statusDetail', $post_response->statusDetail);
                $payment->setAdditionalInformation('vendorTxCode', $vendorTxCode);
                //$payment->setCcLast4($payment->getAdditionalInformation("cc_last4"));

                //save order with pending payment
                $order = $this->_placeOrder();

                if($order){
                    $payment = $order->getPayment();
                    $payment->setTransactionId($transactionId);
                    $payment->setLastTransId($transactionId);
                    $payment->save();

                    //invoice
                    if($post_response->statusCode == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS){
//                        $invoice = $order->prepareInvoice();
//                        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
//                        $invoice->register();
//                        $payment->accept();
                        $payment->getMethodInstance()->markAsInitialized();
                        $order->place()->save();

                        $this->orderSender->send($order);

                        //prepare session to success page
                        $this->_getCheckoutSession()->clearHelperData();
                        //set last successful quote
                        $this->_getCheckoutSession()->setLastQuoteId($this->_quote->getId())
                            ->setLastSuccessQuoteId($this->_quote->getId());
                        $this->_getCheckoutSession()->setLastOrderId($order->getId())
                                ->setLastRealOrderId($order->getIncrementId())
                                ->setLastOrderStatus($order->getStatus());
                    }

                }else{
                    throw new \Magento\Framework\Validator\Exception(__('Unable to save order, please use another payment method.'));
                }

                //prepare response
                $responseContent = [
                    'success' => true,
                    'response' => $post_response
                ];

            }  else {
                throw new \Magento\Framework\Validator\Exception(__('Invalid Sage Pay response, please use another payment method.'));
            }

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $apiException->getUserMessage()),
            ];

        } catch (\Exception $e) {
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $e->getMessage()),
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    protected function _generateRequest($vendorTxCode){

        $billing_address = $this->_quote->getBillingAddress();
        $shipping_address = $this->_quote->getShippingAddress();
        $customer_data = $this->_getCustomerSession()->getCustomerDataObject();

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
            'description' => "Magento transaction.",
            'customerFirstName' => $billing_address->getFirstname(),
            'customerLastName' => $billing_address->getLastname(),
            'billingAddress' => [
                'address1' => $billing_address->getStreetLine(1),
                'city' => $billing_address->getCity(),
                'postalCode' => $billing_address->getPostCode(),
                'country' => $billing_address->getCountryId()
            ],
            'entryMethod' => "Ecommerce",
            'apply3DSecure' => $this->_config->get3Dsecure()
        ];

        if ($billing_address->getCountryId() == "US") {
            $state = $billing_address->getRegionCode();
            if (strlen($state) > 2) {
                $state = "CA"; //hardcoded as the code is not working correctly
            }
            $data["billingAddress"]["state"] = $state;
        }

        return $data;
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

    protected function _placeOrder(){

        $order = $this->_quoteManagement->submit($this->_quote);

        if (!$order) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Can not save order. Please try another payment option.'));
        }

        return $order;
    }
}
