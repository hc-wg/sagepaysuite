<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Paypal;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Callback extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    protected $_checkoutHelper;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    protected $_postData;

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $_curlFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory
     */
    protected $_apiExceptionFactory;

    /**
     * Success constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param OrderSender $orderSender
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        Logger $suiteLogger,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Ebizmarts\SagePaySuite\Model\Api\ApiExceptionFactory $apiExceptionFactory
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);
        $this->_logger = $logger;
        $this->_transactionFactory = $transactionFactory;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_curlFactory = $curlFactory;
        $this->_apiExceptionFactory = $apiExceptionFactory;

        $this->_postData = $this->getRequest()->getPost();
        $this->_quote = $this->_getCheckoutSession()->getQuote();
    }

    /**
     * FORM success callback
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {

            //log response
            $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $this->_postData);

            if (!empty($this->_postData) && isset($this->_postData->Status) && $this->_postData->Status == "PAYPALOK") {

            } else {
                if (!empty($this->_postData) && isset($this->_postData->StatusDetail)) {
                    throw new LocalizedException("Can not place Paypal order: " . $this->_postData->StatusDetail);
                } else {
                    throw new LocalizedException("Can not place Paypal order, please try another payment method");
                }
            }

            //toDo
            //update shipping from paypal and other data

            //send ok to sagepay
            $completion_response = $this->_sendCompletionPost()["data"];

            //log response
            //$this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $completion_response);

            /**
             *  SUCCESSFULLY COMPLETED PAYMENT (CAPTURE, DEFER or AUTH)
             */

            $transactionId = $completion_response["VPSTxId"];
            $transactionId = str_replace("{", "", str_replace("}", "", $transactionId)); //strip brackets

            //import payment info for save order
            $payment = $this->_quote->getPayment();
            $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PAYPAL);
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setCcType("PayPal");
            $payment->setAdditionalInformation('statusDetail', $completion_response["StatusDetail"]);
            $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
            $payment->setAdditionalInformation('mode', $this->_config->getMode());

            $order = $this->_checkoutHelper->placeOrder();
            $quoteId = $this->_quote->getId();

            //prepare session to success or cancellation page
            $this->_getCheckoutSession()->clearHelperData();
            $this->_getCheckoutSession()->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
            //an order may be created
            if ($order) {
                $this->_getCheckoutSession()->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());

                //send email
                $this->_checkoutHelper->sendOrderEmail($order);
            }

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setIsTransactionClosed(1);
            $payment->save();

            switch ($this->_config->getSagepayPaymentAction()) {
                case \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT:
                    $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                    $closed = true;
                    break;
                case \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER:
                    $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                    $closed = false;
                    break;
                case \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE:
                    $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                    $closed = false;
                    break;
                default:
                    $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                    $closed = true;
                    break;
            }

            //create transaction record
            $transaction = $this->_transactionFactory->create()
                ->setOrderPaymentObject($payment)
                ->setTxnId($transactionId)
                ->setOrderId($order->getEntityId())
                ->setTxnType($action)
                ->setPaymentId($payment->getId());
            $transaction->setIsClosed($closed);
            $transaction->save();

            //update invoice transaction id
            $invoices = $order->getInvoiceCollection();
            if ($invoices->count()) {
                foreach ($invoices as $_invoice) {
                    $_invoice->setTransactionId($payment->getLastTransId());
                    $_invoice->save();
                }
            }

            $this->_redirect('checkout/onepage/success');

            return;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->_redirectToCartAndShowError('We can\'t place the order. Please try another payment method. ' . $e->getMessage());
        }
    }

    protected function _sendCompletionPost()
    {
        $request = array(
            "VPSProtocol" => $this->_config->getVPSProtocol(),
            "TxType" => "COMPLETE",
            "VPSTxId" => $this->_postData->VPSTxId,
            "Amount" => number_format($this->_quote->getGrandTotal(), 2, '.', ''),
            "Accept" => "YES"
        );
        return $this->_handleApiErrors($this->_sendPost($request));
    }

    /**
     * Redirect customer to shopping cart and show error message
     *
     * @param string $errorMessage
     * @return void
     */
    protected function _redirectToCartAndShowError($errorMessage)
    {
        $this->messageManager->addError($errorMessage);
        $this->_redirect('checkout/cart');
    }

    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCustomerSession()
    {
        return $this->_customerSession;
    }

    private function _getServiceURL()
    {
        if ($this->_config->getMode() == \Ebizmarts\SagePaySuite\Model\Config::MODE_LIVE) {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PAYPAL_COMPLETION_LIVE;
        } else {
            return \Ebizmarts\SagePaySuite\Model\Config::URL_PAYPAL_COMPLETION_TEST;
        }
    }

    protected function _handleApiErrors($response)
    {
        $exceptionPhrase = "Invalid response from PayPal";
        $exceptionCode = 0;

        if ($response["status"] == 200) {

            if (!empty($response) && array_key_exists("data", $response)) {
                if (array_key_exists("Status", $response["data"]) && (
                        $response["data"]["Status"] == 'OK' ||
                        $response["data"]["Status"] == 'REGISTERED' ||
                        $response["data"]["Status"] == 'AUTHENTICATED'
                    )
                ) {

                    //this is a successfull response
                    return $response;

                } else {

                    //there was an error
                    $detail = explode(":", $response["data"]["StatusDetail"]);
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

    protected function _sendPost($postData)
    {

        $curl = $this->_curlFactory->create();
        $url = $this->_getServiceURL();

        $post_data_string = '';
        foreach ($postData as $_key => $_val) {
            $post_data_string .= $_key . '=' . urlencode(mb_convert_encoding($_val, 'ISO-8859-1', 'UTF-8')) . '&';
        }

        //log request
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $postData);

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

        //log response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $data);

        $response_data = [];
        if ($response_status == 200) {

            //parse response
            $data = preg_split('/^\r?$/m', $data, 2);
            $data = explode(chr(13), $data[1]);

            for ($i = 0; $i < count($data); $i++) {
                if (!empty($data[$i])) {
                    $aux = explode("=", trim($data[$i]));
                    if (count($aux) == 2) {
                        $response_data[$aux[0]] = $aux[1];
                    } else {
                        if (count($aux) > 2) {
                            $response_data[$aux[0]] = $aux[1];
                            for ($j = 2; $j < count($aux); $j++) {
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

}
