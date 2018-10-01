<?php
/**
 * Copyright © 2018 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\Repeat;

use Ebizmarts\SagePaySuite\Helper\Checkout as SuiteHelperCheckout;
use Ebizmarts\SagePaySuite\Helper\Data as SuiteHelper;
use Ebizmarts\SagePaySuite\Helper\Request as SuiteHelperRequest;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Api\Shared as SuiteSharedApi;
use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Config\ClosedForActionFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order\Payment\TransactionFactory;

class Request extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var SuiteHelper
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
     * @var SuiteHelperCheckout
     */
    private $_checkoutHelper;

    /**
     *  POST array
     */
    private $_postData;

    /**
     * @var QuoteManagement
     */
    private $_quoteManagement;

    /**
     * Sage Pay Suite Request Helper
     * @var SuiteHelperRequest
     */
    private $_requestHelper;

    /**
     * @var SuiteSharedApi
     */
    private $_sharedApi;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /** @var ClosedForActionFactory */
    private $actionFactory;


    public function __construct(
        Context $context,
        Config $config,
        SuiteHelper $suiteHelper,
        Logger $suiteLogger,
        Quote $quoteSession,
        SuiteHelperCheckout $checkoutHelper,
        QuoteManagement $quoteManagement,
        SuiteHelperRequest $requestHelper,
        SuiteSharedApi $sharedApi,
        TransactionFactory $transactionFactory,
        ClosedForActionFactory $actionFactory
    ) {
    
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_REPEAT);
        $this->_suiteHelper       = $suiteHelper;
        $this->_suiteLogger       = $suiteLogger;
        $this->_sharedApi         = $sharedApi;
        $this->_checkoutHelper    = $checkoutHelper;
        $this->_quoteManagement   = $quoteManagement;
        $this->_requestHelper     = $requestHelper;
        $this->transactionFactory = $transactionFactory;
        $this->actionFactory      = $actionFactory;
        $this->_quote             = $quoteSession->getQuote();
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
                $this->_config->getSagepayPaymentAction()
            );

            //set payment info for save order

            //strip brackets
            $transactionId = str_replace("{", "", str_replace("}", "", $post_response["data"]["VPSTxId"]));

            $payment = $this->_quote->getPayment();
            $payment->setMethod(Config::METHOD_REPEAT);
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
                $this->_confirmPayment($transactionId, $order);

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
        } catch (ApiException $apiException) {
            $this->_suiteLogger->logException($apiException, [__METHOD__, __LINE__]);
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: %1', $apiException->getUserMessage()),
            ];
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e, [__METHOD__, __LINE__]);
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: %1', $e->getMessage()),
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    private function _generateRequest($vendorTxCode)
    {
        $data = [];

        $data['VendorTxCode'] = $vendorTxCode;
        $data['Description']  = $this->_requestHelper->getOrderDescription(true);
        $data['ReferrerID']   = $this->_requestHelper->getReferrerId();

        //populate payment amount information
        $amount = $this->_requestHelper->populatePaymentAmountAndCurrency($this->_quote);
        $data = array_merge($data, $amount);

        //populate address information
        $data = array_merge($data, $this->_requestHelper->populateAddressInformation($this->_quote));

        return $data;
    }

    private function _confirmPayment($transactionId, $order)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        /** @var \Magento\Sales\Model\Order $order */
        $payment = $order->getPayment();
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);

        $sagePayPaymentAction = $this->_config->getSagepayPaymentAction();

        //leave transaction open in case defer
        if ($sagePayPaymentAction === Config::ACTION_REPEAT_DEFERRED) {
            /** @var \Ebizmarts\SagePaySuite\Model\Config\ClosedForAction $actionClosed */
            $actionClosed = $this->actionFactory->create(['paymentAction' => $sagePayPaymentAction]);
            list($action, $closed) = $actionClosed->getActionClosedForPaymentAction();

            /** @var \Magento\Sales\Model\Order\Payment\Transaction $transaction */
            $transaction = $this->transactionFactory->create();
            $transaction->setOrderPaymentObject($payment);
            $transaction->setTxnId($transactionId);
            $transaction->setOrderId($order->getEntityId());
            $transaction->setTxnType($action);
            $transaction->setPaymentId($payment->getId());
            $transaction->setIsClosed($closed);
            $transaction->save();
        }

        $payment->save();

        if ($sagePayPaymentAction === Config::ACTION_REPEAT) {
            $payment->getMethodInstance()->markAsInitialized();
        }

        $order->place()->save();

        //send email
        $this->_checkoutHelper->sendOrderEmail($order);
    }
}
