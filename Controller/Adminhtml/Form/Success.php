<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\Form;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Success extends \Magento\Backend\App\AbstractAction
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
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    private $_transactionFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Checkout
     */
    private $_checkoutHelper;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form
     */
    private $_formModel;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $_quoteSession;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    private $_quoteManagement;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     * @param \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper
     * @param \Ebizmarts\SagePaySuite\Model\Form $formModel
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        Logger $suiteLogger,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Model\Form $formModel,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
    
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->_transactionFactory = $transactionFactory;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_formModel = $formModel;
        $this->_quoteSession = $quoteSession;
        $this->_quoteManagement = $quoteManagement;
    }

    /**
     * FORM success callback
     * @throws Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $order = null;

        try {
            //decode response
            $response = $this->_formModel->decodeSagePayResponse($this->getRequest()->getParam("crypt"));
            if (!array_key_exists("VPSTxId", $response)) {
                throw new \Magento\Framework\Exception\LocalizedException('Invalid response from Sage Pay');
            }

            //log response
            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $response);

            $this->_quote = $this->_quoteSession->getQuote();

            $transactionId = $response["VPSTxId"];
            $transactionId = str_replace("{", "", str_replace("}", "", $transactionId)); //strip brackets

            //import payment info for save order
            $payment = $this->_quote->getPayment();
            $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setCcType($response["CardType"]);
            $payment->setCcLast4($response["Last4Digits"]);
            if (array_key_exists("ExpiryDate", $response)) {
                $payment->setCcExpMonth(substr($response["ExpiryDate"], 0, 2));
                $payment->setCcExpYear(substr($response["ExpiryDate"], 2));
            }
            if (array_key_exists("3DSecureStatus", $response)) {
                $payment->setAdditionalInformation('threeDStatus', $response["3DSecureStatus"]);
            }
            $payment->setAdditionalInformation('statusDetail', $response["StatusDetail"]);
            $payment->setAdditionalInformation('vendorTxCode', $response["VendorTxCode"]);
            $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
            $payment->setAdditionalInformation('mode', $this->_config->getMode());
            $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());

            $order = $this->_quoteManagement->submit($this->_quote);

            //an order may be created
            if ($order) {
            //send email
                $this->_checkoutHelper->sendOrderEmail($order);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Can not create order'));
            }

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setIsTransactionClosed(1);
            $payment->save();

            list($action, $closed) = $this->getActionClosedForPaymentAction();

            //create transaction record
            $transaction = $this->_transactionFactory->create();
            $transaction->setOrderPaymentObject($payment);
            $transaction->setTxnId($transactionId);
            $transaction->setOrderId($order->getEntityId());
            $transaction->setTxnType($action);
            $transaction->setPaymentId($payment->getId());
            $transaction->setIsClosed($closed);
            $transaction->save();

            //update invoice transaction id
            $order->getInvoiceCollection()
                ->setDataToAll('transaction_id', $payment->getLastTransId())
                ->save();

            //add success url to response
            $route = 'sales/order/view';
            $param['order_id'] = $order->getId();
            $url = $this->_backendUrl->getUrl($route, $param);
            $this->_redirect($url);

            return;
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e);

            if ($order) {
                $this->messageManager->addError($e->getMessage());
                $route = 'sales/order/view';
                $param['order_id'] = $order->getId();
                $url = $this->_backendUrl->getUrl($route, $param);
            } else {
                $this->messageManager
                    ->addError("Your payment was successful but the order was NOT created: " . $e->getMessage());
                $route = 'sales/order/view';
                $url = $this->_backendUrl->getUrl($route, []);
            }

            $this->_redirect($url);
        }
    }

    /**
     * @return array
     */
    private function getActionClosedForPaymentAction()
    {
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
        return [$action, $closed];
    }
}
