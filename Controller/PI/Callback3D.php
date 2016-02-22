<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\PI;


use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Callback3D extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRest
     */
    protected $_pirest;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    protected $_postData;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirest
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirest,
        Logger $suiteLogger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderSender $orderSender,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Psr\Log\LoggerInterface $logger
    )
    {
        parent::__construct($context);

        $this->_pirest = $pirest;
        $this->_suiteLogger = $suiteLogger;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender = $orderSender;
        $this->_transactionFactory = $transactionFactory;
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_logger = $logger;

        $this->_postData = $this->getRequest()->getPost();;
    }

    public function execute()
    {
        //log response
        //$this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $this->_postData);

        try {

            //submit 3D secure
            $paRes = $this->_postData->PaRes;
            $transactionId = $this->getRequest()->getParam("transactionId");
            $submit3D_result = $this->_pirest->submit3D($paRes,$transactionId);

            if (isset($submit3D_result->status) && $submit3D_result->status == "Authenticated"){

                //request transaction details to confirm payment
                $transaction_details_result = $this->_pirest->transactionDetails($transactionId);

                //log transaction details response
                $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $transaction_details_result);

                $this->_confirmPayment($transaction_details_result);

                $this->_redirect('checkout/onepage/success');

            } else {

                $this->messageManager->addError("Invalid 3D secure authentication.");
                $this->_redirect('checkout/cart');
            }

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            $this->messageManager->addError($apiException->getUserMessage());
            $this->_redirect('checkout/cart');


        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->messageManager->addError("Something went wrong while authenticating 3D secure: " . $e->getMessage());
            $this->_redirect('checkout/cart');
        }
    }

    protected function _confirmPayment($response)
    {

        if ($response->statusCode == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {

            $transactionId = $this->getRequest()->getParam("transactionId");
            $order = $this->_orderFactory->create()->load($this->getRequest()->getParam("orderId"));
            $quoteId = $this->getRequest()->getParam("quoteId");

            if (!empty($order)) {

                //set additional payment info
                $payment = $order->getPayment();
                $payment->setAdditionalInformation('statusCode', $response->statusCode);
                $payment->setAdditionalInformation('statusDetail', $response->statusDetail);
                $payment->setAdditionalInformation('threeDStatus', $response->{'3DSecure'}->status);
                $payment->save();

                //invoice
                $payment->getMethodInstance()->markAsInitialized();
                $order->place()->save();

                //send email
                $this->_orderSender->send($order);

                //create transaction record
                switch($this->_config->getSagepayPaymentAction())
                {
                    case \Ebizmarts\SagePaySuite\Model\Config::ACTION_PAYMENT:
                        $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                        $closed = true;
                        break;
                    default:
                        $action = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                        $closed = true;
                        break;
                }
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
                if($invoices->count()){
                    foreach ($invoices as $_invoice) {
                        $_invoice->setTransactionId($payment->getLastTransId());
                        $_invoice->save();
                    }
                }

                //prepare session to success page
                $this->_getCheckoutSession()->clearHelperData();
                $this->_getCheckoutSession()->setLastQuoteId($quoteId)
                    ->setLastSuccessQuoteId($quoteId);
                $this->_getCheckoutSession()->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());

            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to save order, please use another payment method.'));
            }
        } else {
            throw new \Magento\Framework\Validator\Exception(__('Invalid Sage Pay response, please use another payment method.'));
        }
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

}
