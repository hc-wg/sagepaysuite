<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\PI;

use Magento\Framework\Controller\ResultFactory;

class Request extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $_quoteSession;

    /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\MotoManagement */
    private $requester;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager */
    private $piRequestManagerDataFactory;

    /**
     * Request constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Ebizmarts\SagePaySuite\Model\PiRequestManagement\MotoManagement $requester
     * @param \Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory $piReqManagerFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Ebizmarts\SagePaySuite\Model\PiRequestManagement\MotoManagement $requester,
        \Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory $piReqManagerFactory
    ) {
        parent::__construct($context);
        $this->_config                     = $config;
        $this->_quoteSession    = $quoteSession;
        $this->_quote           = $this->_quoteSession->getQuote();

        $this->requester                   = $requester;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
    }

    public function execute()
    {
        /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager $data */
        $data = $this->piRequestManagerDataFactory->create();
        $data->setMode($this->_config->getMode());
        $data->setVendorName($this->_config->getVendorname());
        $data->setPaymentAction($this->_config->getSagepayPaymentAction());
        $data->setMerchantSessionKey($this->getRequest()->getPost('merchant_session_key'));
        $data->setCardIdentifier($this->getRequest()->getPost('card_identifier'));
        $data->setCcExpMonth($this->getRequest()->getPost('card_exp_month'));
        $data->setCcExpYear($this->getRequest()->getPost('card_exp_year'));
        $data->setCcLastFour($this->getRequest()->getPost('card_last4'));
        $data->setCcType($this->getRequest()->getPost('card_type'));

        $this->requester->setRequestData($data);
        $this->requester->setQuote($this->_quote);

        $response = $this->requester->placeOrder();

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($response->__toArray());
        return $resultJson;
    }

//    public function execute()
//    {
//        try {
//            //parse POST data
//            $this->_postData = $this->getRequest()->getPost();
//
//            //prepare quote
//            $this->_quote->collectTotals();
//            $this->_quote->reserveOrderId();
//            $vendorTxCode = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());
//
//            //generate POST request
//            $request = $this->piRequest
//                ->setCart($this->_quote)
//                ->setCardIdentifier($this->_postData->card_identifier)
//                ->setIsMoto(true)
//                ->setMerchantSessionKey($this->_postData->merchant_session_key)
//                ->setVendorTxCode($vendorTxCode)
//                ->getRequestData();
//
//            //send POST to Sage Pay
//            $postResponse = $this->_pirestapi->capture($request);
//
//            if ($postResponse->getStatusCode() == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {
//                //set payment info for save order
//                $transactionId = $postResponse->getTransactionId();
//                $payment       = $this->_quote->getPayment();
//                $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
//                $payment->setTransactionId($transactionId);
//
//                //DropIn
//                if ($postResponse->getPaymentMethod() !== null) {
//                    /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultCard $card */
//                    $card = $postResponse->getPaymentMethod()->getCard();
//                    if ($card !== null) {
//                        $payment->setCcLast4($card->getLastFourDigits());
//                        $payment->setCcExpMonth($card->getExpiryMonth());
//                        $payment->setCcExpYear($card->getExpiryYear());
//                        $payment->setCcType($this->ccConverter->convert($card->getCardType()));
//                    }
//                }
//                else {
//                    //Custom cc form
//                    $payment->setCcLast4($this->_postData->card_last4);
//                    $payment->setCcExpMonth($this->_postData->card_exp_month);
//                    $payment->setCcExpYear($this->_postData->card_exp_year);
//                    $payment->setCcType($this->ccConverter->convert($this->_postData->card_type));
//                }
//
//                $payment->setAdditionalInformation('statusCode', $postResponse->getStatusCode());
//                $payment->setAdditionalInformation('statusDetail', $postResponse->getStatusDetail());
//                $payment->setAdditionalInformation('vendorTxCode', $vendorTxCode);
//                if (isset($postResponse->{'3DSecure'})) {
//                    $payment->setAdditionalInformation('threeDStatus', $postResponse->{'3DSecure'}->status);
//                }
//                $payment->setAdditionalInformation('moto', true);
//                $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());
//                $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
//                $payment->setAdditionalInformation('mode', $this->_config->getMode());
//
//                //save order with pending payment
//                $order = $this->_getOrderCreateModel()
//                    ->setIsValidate(true)
//                    ->importPostData($this->getRequest()->getPost('order'))
//                    ->createOrder();
//
//                if ($order) {
//                    $this->_confirmPayment($transactionId, $order);
//
//                    //add success url to response
//                    $route = 'sales/order/view';
//                    $param['order_id'] = $order->getId();
//                    $url = $this->_backendUrl->getUrl($route, $param);
//                    $postResponse->redirect = $url;
//
//                    //prepare response
//                    $responseContent = [
//                        'success' => true,
//                        'response' => $postResponse
//                    ];
//                } else {
//                    throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order.'));
//                }
//            } else {
//                throw new \Magento\Framework\Validator\Exception(__('Invalid Sage Pay response.'));
//            }
//        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
//            $this->_suiteLogger->logException($apiException, [__METHOD__, __LINE__]);
//            $responseContent = [
//                'success' => false,
//                'error_message' => __('Something went wrong: ' . $apiException->getUserMessage()),
//            ];
//        } catch (\Exception $e) {
//            $this->_suiteLogger->logException($e, [__METHOD__, __LINE__]);
//            $responseContent = [
//                'success' => false,
//                'error_message' => __('Something went wrong: ' . $e->getMessage()),
//            ];
//        }
//
//        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
//        $resultJson->setData($responseContent);
//        return $resultJson;
//    }
//
//    /**
//     * Retrieve order create model
//     *
//     * @return \Magento\Sales\Model\AdminOrder\Create
//     */
//    private function _getOrderCreateModel()
//    {
//        return $this->_objectManager->get('Magento\Sales\Model\AdminOrder\Create');
//    }
//
//    private function _confirmPayment($transactionId, $order)
//    {
//        $payment = $order->getPayment();
//        $payment->setTransactionId($transactionId);
//        $payment->setLastTransId($transactionId);
//
//        //leave transaction open in case defer or authorize
//        if ($this->_config->getSagepayPaymentAction() == Config::ACTION_AUTHENTICATE ||
//            $this->_config->getSagepayPaymentAction() == Config::ACTION_DEFER) {
//            $payment->setIsTransactionClosed(0);
//        }
//
//        $payment->save();
//
//        $payment->getMethodInstance()->markAsInitialized();
//        $order->place()->save();
//
//        //send email
//        $this->_checkoutHelper->sendOrderEmail($order);
//    }
}
