<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\PI;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Api\PIRest;

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
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRest
     */
    private $_pirestapi;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    private $_checkoutHelper;

    /**
     *  POST array
     */
    private $_postData;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $_quoteSession;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    private $_quoteManagement;

    /**
     * Sage Pay Suite Request Helper
     * @var \Ebizmarts\SagePaySuite\Helper\Request
     */
    private $_requestHelper;

    /** @var \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType */
    private $ccConverter;

    /** @var \Ebizmarts\SagePaySuite\Model\PiRequest */
    private $piRequest;

    /**
     * Request constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param Config $config
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     * @param Logger $suiteLogger
     * @param PIRest $pirestapi
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Ebizmarts\SagePaySuite\Helper\Request $requestHelper
     * @param Config\SagePayCardType $ccConvert
     * @param \Ebizmarts\SagePaySuite\Model\PiRequest $piRequest
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        Logger $suiteLogger,
        PIRest $pirestapi,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType $ccConvert,
        \Ebizmarts\SagePaySuite\Model\PiRequest $piRequest
    ) {
    
        parent::__construct($context);
        $this->_config          = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_suiteHelper     = $suiteHelper;
        $this->_suiteLogger     = $suiteLogger;
        $this->_pirestapi       = $pirestapi;
        $this->_checkoutHelper  = $checkoutHelper;
        $this->_customerSession = $customerSession;
        $this->_quoteSession    = $quoteSession;
        $this->_quoteManagement = $quoteManagement;
        $this->_requestHelper   = $requestHelper;
        $this->_quote           = $this->_quoteSession->getQuote();
        $this->ccConverter      = $ccConvert;
        $this->piRequest        = $piRequest;
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
            $request = $this->piRequest
                ->setCart($this->_quote)
                ->setCardIdentifier($this->_postData->card_identifier)
                ->setIsMoto(true)
                ->setMerchantSessionKey($this->_postData->merchant_session_key)
                ->setVendorTxCode($vendorTxCode)
                ->getRequestData();

            //send POST to Sage Pay
            $post_response = $this->_pirestapi->capture($request);

            if ($post_response->statusCode == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {
                //set payment info for save order
                $transactionId = $post_response->transactionId;
                $payment = $this->_quote->getPayment();
                $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
                $payment->setTransactionId($transactionId);

                //DropIn
                if (isset($post_response->paymentMethod)) {
                    if (isset($post_response->paymentMethod->card)) {
                        $card = $post_response->paymentMethod->card;
                        $payment->setCcLast4($card->lastFourDigits);
                        $payment->setCcExpMonth(substr($card->expiryDate, 0, 2));
                        $payment->setCcExpYear(substr($card->expiryDate, 2, 2));
                        $payment->setCcType($this->ccConverter->convert($card->cardType));
                    }
                }
                else {
                    //Custom cc form
                    $payment->setCcLast4($this->_postData->card_last4);
                    $payment->setCcExpMonth($this->_postData->card_exp_month);
                    $payment->setCcExpYear($this->_postData->card_exp_year);
                    $payment->setCcType($this->ccConverter->convert($this->_postData->card_type));
                }

                $payment->setAdditionalInformation('statusCode', $post_response->statusCode);
                $payment->setAdditionalInformation('statusDetail', $post_response->statusDetail);
                $payment->setAdditionalInformation('vendorTxCode', $vendorTxCode);
                if (isset($post_response->{'3DSecure'})) {
                    $payment->setAdditionalInformation('threeDStatus', $post_response->{'3DSecure'}->status);
                }
                $payment->setAdditionalInformation('moto', true);
                $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());
                $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
                $payment->setAdditionalInformation('mode', $this->_config->getMode());

                //save order with pending payment
                $order = $this->_getOrderCreateModel()
                    ->setIsValidate(true)
                    ->importPostData($this->getRequest()->getPost('order'))
                    ->createOrder();

                if ($order) {
                    $this->_confirmPayment($transactionId, $order);

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
            $this->_suiteLogger->logException($apiException, [__METHOD__, __LINE__]);
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $apiException->getUserMessage()),
            ];
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e, [__METHOD__, __LINE__]);
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $e->getMessage()),
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    /**
     * Retrieve order create model
     *
     * @return \Magento\Sales\Model\AdminOrder\Create
     */
    private function _getOrderCreateModel()
    {
        return $this->_objectManager->get('Magento\Sales\Model\AdminOrder\Create');
    }

    private function _confirmPayment($transactionId, $order)
    {
        $payment = $order->getPayment();
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);

        //leave transaction open in case defer or authorize
        if ($this->_config->getSagepayPaymentAction() == Config::ACTION_AUTHENTICATE ||
            $this->_config->getSagepayPaymentAction() == Config::ACTION_DEFER) {
            $payment->setIsTransactionClosed(0);
        }

        $payment->save();

        $payment->getMethodInstance()->markAsInitialized();
        $order->place()->save();

        //send email
        $this->_checkoutHelper->sendOrderEmail($order);
    }
}
