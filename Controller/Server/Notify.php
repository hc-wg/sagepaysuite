<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Server;


use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Notify extends \Magento\Framework\App\Action\Action
{

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
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var array
     */
    protected $_postData;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Token
     */
    protected $_tokenModel;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Notify constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\SagePaySuite\Model\Token $tokenModel
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $suiteLogger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderSender $orderSender,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Model\Token $tokenModel,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    )
    {
        parent::__construct($context);

        $this->_suiteLogger        = $suiteLogger;
        $this->_orderFactory       = $orderFactory;
        $this->_orderSender        = $orderSender;
        $this->_transactionFactory = $transactionFactory;
        $this->_config             = $config;
        $this->_checkoutSession    = $checkoutSession;
        $this->_tokenModel         = $tokenModel;
        $this->_quote              = $quote;
        $this->quoteRepository     = $quoteRepository;

        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
    }

    public function execute()
    {

        //get data from request
        $this->_postData = $this->getRequest()->getPost();

        //log response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $this->_postData);

        $quoteId      = $this->getRequest()->getParam("quoteid");
        $this->_quote = $this->quoteRepository->get($quoteId);

        try {

            //find quote with GET param
            if (empty($this->_quote->getId())) {
                return $this->_returnInvalid("Unable to find quote");
            }

            //find order with quote id
            $order = $this->_orderFactory->create()->loadByIncrementId($this->_quote->getReservedOrderId());
            if (is_null($order) || is_null($order->getId())) {
                return $this->_returnInvalid("Order was not found");
            }

            $this->_order = $order;
            $payment      = $order->getPayment();

            //get some vars from POST
            $status        = $this->_postData->Status;
            $transactionId = str_replace(array("}", "{"), array(""), $this->_postData->VPSTxId); //strip brackets

            //validate hash
            $localMd5Hash = md5($this->_getVPSSignatureString($payment));

            if (strtoupper($localMd5Hash) != $this->_postData->VPSSignature) {
                //log full values for VPS signature
                $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, "INVALID SIGNATURE: " . $this->_getVPSSignatureString($payment));
                throw new \Magento\Framework\Validator\Exception(__('Invalid VPS Signature'));
            }

            //update payment details
            if (!empty($transactionId) && $payment->getLastTransId() == $transactionId) { //validate transaction id
                $payment->setAdditionalInformation('statusDetail', $this->_postData->StatusDetail);
                $payment->setAdditionalInformation('threeDStatus', $this->_postData->{'3DSecureStatus'});
                $payment->setCcType($this->_postData->CardType);
                $payment->setCcLast4($this->_postData->Last4Digits);
                $payment->setCcExpMonth(substr($this->_postData->ExpiryDate, 0, 2));
                $payment->setCcExpYear(substr($this->_postData->ExpiryDate, 2));
                $payment->save();
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Invalid transaction id'));
            }

            if (isset($this->_postData->Token)) {
                //save token

                $this->_tokenModel->saveToken($order->getCustomerId(),
                    $this->_postData->Token,
                    $this->_postData->CardType,
                    $this->_postData->Last4Digits,
                    substr($this->_postData->ExpiryDate, 0, 2),
                    substr($this->_postData->ExpiryDate, 2),
                    $this->_config->getVendorname()
                );
            }

            /**
             * OK = Process executed without error.
             *
             * NOTAUTHED = The Sage Pay gateway could not authorise the
             * transaction because the details provided by the customer were
             * incorrect, or insufficient funds were available. However the
             * transaction has completed.
             *
             * PENDING = This only affects European Payment methods.
             * Indicates a transaction has yet to fail or succeed. This will be
             * updated by Sage Pay when we receive a notification from PPRO.
             * The updated status can be seen in MySagePay.
             *
             * ABORT = The Transaction could not be completed because the
             * user clicked the CANCEL button on the payment pages, or went
             * inactive for 15 minutes or longer.
             *
             * REJECTED = The Sage Pay System rejected the transaction
             * Appendix B: Notification of Transaction Results
             * Sage Pay Server Integration and Protocol and Guidelines 3.00 Page 65 of 72
             * because of the fraud screening rules you have set on your
             * account.
             * Note: The bank may have authorised the transaction but your
             * own rule bases for AVS/CV2 or 3D-Secure caused the
             * transaction to be rejected.
             *
             * AUTHENTICATED = The 3D-Secure checks were performed
             * successfully and the card details secured at Sage Pay. Only
             * returned if TxType is AUTHENTICATE.
             *
             * REGISTERED = 3D-Secure checks failed or were not performed,
             * but the card details are still secured at Sage Pay. Only returned if
             * TxType is AUTHENTICATE.
             *
             * ERROR = A problem occurred at Sage Pay which prevented
             * transaction registration.
             *
             */

            if ($status == "ABORT") { //Transaction canceled by customer

                //cancel pending payment order
                $this->_cancelOrder($order);

                return $this->_returnAbort();

            } elseif ($status == "OK" || $status == "AUTHENTICATED" || $status == "REGISTERED") { //Transaction succeeded or authenticated

                $sendEmail = true;
                if ($payment->getAdditionalInformation('euroPayment') == true) {
                    //don't send email if EURO PAYMENT as it was already sent
                    $sendEmail = false;
                }

                $this->_confirmPayment($transactionId, $sendEmail);

                return $this->_returnOk();

            } elseif ($status == "PENDING") { //Transaction in PENDING state (this is just for Euro Payments which are not yet available in this version)

                $payment->setAdditionalInformation('euroPayment', true);

                //send order email
                $this->_orderSender->send($this->_order);

                return $this->_returnOk();

            } else { //Transaction failed with NOTAUTHED, REJECTED or ERROR

                //cancel pending payment order
                $order->cancel()->save();

                return $this->_returnInvalid("Payment was not accepted, please try another payment method");
            }

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_suiteLogger->logException($apiException);

            //cancel pending payment order
            $this->_cancelOrder($order);

            return $this->_returnInvalid("Something went wrong: " . $apiException->getUserMessage());

        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e);

            //cancel pending payment order
            $this->_cancelOrder($order);

            return $this->_returnInvalid("Something went wrong: " . $e->getMessage());
        }
    }

    protected function _getVPSSignatureString($payment)
    {
        return $this->_postData->VPSTxId .
        $this->_postData->VendorTxCode .
        $this->_postData->Status .
        (property_exists($this->_postData, 'TxAuthNo') === TRUE ? $this->_postData->TxAuthNo : '') .
        strtolower($payment->getAdditionalInformation('vendorname')) .
        $this->_postData->AVSCV2 .
        $payment->getAdditionalInformation('securityKey') .
        $this->_postData->AddressResult .
        $this->_postData->PostCodeResult .
        $this->_postData->CV2Result .
        $this->_postData->GiftAid .
        $this->_postData->{'3DSecureStatus'} .
        (property_exists($this->_postData, 'CAVV') === TRUE ? $this->_postData->CAVV : '') .
        $this->_postData->AddressStatus .
        $this->_postData->PayerStatus .
        $this->_postData->CardType .
        $this->_postData->Last4Digits .
        (property_exists($this->_postData, 'DeclineCode') === TRUE ? $this->_postData->DeclineCode : '') .
        $this->_postData->ExpiryDate .
        (property_exists($this->_postData, 'FraudResponse') === TRUE ? $this->_postData->FraudResponse : '') .
        (property_exists($this->_postData, 'BankAuthCode') === TRUE ? $this->_postData->BankAuthCode : '');
    }

    protected function _cancelOrder($order)
    {
        try {
            $order->cancel()->save();

            //recover quote
            if ($this->_quote->getId()) {
                $this->_quote->setIsActive(1);
                $this->_quote->setReservedOrderId(NULL);
                $this->_quote->save();

                $this->_checkoutSession->replaceQuote($this->_quote);
            }

            //Unset data
            $this->_checkoutSession->unsLastRealOrderId();

        } catch (\Exception $e) {
            Mage::logException($e);
        }
    }

    protected function _confirmPayment($transactionId, $sendEmail = true)
    {
        //invoice
        $payment = $this->_order->getPayment();
        $payment->getMethodInstance()->markAsInitialized();
        $this->_order->place();

        //send email
        if ($sendEmail) {
            $this->_orderSender->send($this->_order);
        }

        //create transaction record
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

        $transaction = $this->_transactionFactory->create();
        $transaction->setOrderPaymentObject($payment);
        $transaction->setTxnId($transactionId);
        $transaction->setOrderId($this->_order->getEntityId());
        $transaction->setTxnType($action);
        $transaction->setPaymentId($payment->getId());
        $transaction->setIsClosed($closed);
        $transaction->save();

        //update invoice transaction id
        $invoices = $this->_order->getInvoiceCollection();
        if (!empty($invoices)) {
            foreach ($invoices as $_invoice) {
                $_invoice->setTransactionId($payment->getLastTransId());
                $_invoice->save();
            }
        }
    }

    protected function _returnAbort()
    {
        $strResponse = 'Status=OK' . "\r\n";
        $strResponse .= 'StatusDetail=Transaction ABORTED successfully' . "\r\n";
        $strResponse .= 'RedirectURL=' . $this->_getAbortRedirectUrl() . "\r\n";

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        //log our response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $strResponse);

        return;
    }

    protected function _returnOk()
    {
        $strResponse = 'Status=OK' . "\r\n";
        $strResponse .= 'StatusDetail=Transaction completed successfully' . "\r\n";
        $strResponse .= 'RedirectURL=' . $this->_getSuccessRedirectUrl() . "\r\n";

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        //log our response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $strResponse);

        return;
    }

    protected function _returnInvalid($message = 'Invalid transaction, please try another payment method')
    {
        $strResponse = 'Status=INVALID' . "\r\n";
        $strResponse .= 'StatusDetail=' . $message . "\r\n";
        $strResponse .= 'RedirectURL=' . $this->_getFailedRedirectUrl($message) . "\r\n";

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        //log our response
        $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $strResponse);

        return;
    }

    protected function _getAbortRedirectUrl()
    {
        $url = $this->_url->getUrl('*/*/cancel', array(
            '_secure' => true,
            //'_store' => $this->getRequest()->getParam('_store')
        ));

        $url .= "?message=Transaction cancelled by customer";

        return $url;
    }

    protected function _getSuccessRedirectUrl()
    {
        $url = $this->_url->getUrl('*/*/success', array(
            '_secure' => true,
            //'_store' => $this->getRequest()->getParam('_store')
        ));

        $url .= "?quoteid=" . $this->_quote->getId();

        return $url;
    }

    protected function _getFailedRedirectUrl($message)
    {
        $url = $this->_url->getUrl('*/*/cancel', array(
            '_secure' => true,
            //'_store' => $this->getRequest()->getParam('_store')
        ));

        $url .= "?message=" . $message;

        return $url;
    }

}
