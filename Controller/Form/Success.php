<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Success extends \Magento\Framework\App\Action\Action
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

    /**
     * @var \Crypt_AES
     */
    protected $_crypt;

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
        \Crypt_AES $crypt
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->_logger = $logger;
        $this->_transactionFactory = $transactionFactory;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_suiteLogger = $suiteLogger;
        $this->_crypt = $crypt;
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

            $response = $this->decodeSagePayResponse($this->getRequest()->getParam("crypt"));

            //log response
            $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $response);

            $this->_quote = $this->_getCheckoutSession()->getQuote();

            $this->_quote->save();

            $transactionId = $response["VPSTxId"];
            $transactionId = str_replace("{","",str_replace("}","",$transactionId)); //strip brackets

            //import payment info for save order
            $payment = $this->_quote->getPayment();
            $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setCcType($response["CardType"]);
            $payment->setCcLast4($response["Last4Digits"]);
            if(array_key_exists("ExpiryDate",$response)){
                $payment->setCcExpMonth(substr($response["ExpiryDate"],0,2));
                $payment->setCcExpYear(substr($response["ExpiryDate"],2));
            }
            if(array_key_exists("3DSecureStatus",$response)){
                $payment->setAdditionalInformation('threeDStatus',$response["3DSecureStatus"]);
            }
            $payment->setAdditionalInformation('statusDetail', $response["StatusDetail"]);
            $payment->setAdditionalInformation('vendorTxCode', $response["VendorTxCode"]);
            $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
            $payment->setAdditionalInformation('mode', $this->_config->getMode());
            $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());

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

            switch($this->_config->getSagepayPaymentAction())
            {
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
            if($invoices->count()){
                foreach ($invoices as $_invoice) {
                    $_invoice->setTransactionId($payment->getLastTransId());
                    $_invoice->save();
                }
            }

            $this->_redirect('checkout/onepage/success');

            return;

        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->_redirectToCartAndShowError('We can\'t place the order. Please try another payment method.');
        }
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

    protected function decodeSagePayResponse($crypt){
        if (empty($crypt)) {
            $this->_redirectToCartAndShowError('Invalid response from SagePay, please contact our support team to rectify payment.');
        }else{
            $strDecoded = $this->decrypt($crypt);

            $responseRaw = explode('&',$strDecoded);
            $response = array();

            for($i = 0;$i < count($responseRaw);$i++){
                $strField = explode('=',$responseRaw[$i]);
                $response[$strField[0]] = $strField[1];
            }

            if(!array_key_exists(\Ebizmarts\SagePaySuite\Model\Config::VAR_VPSTxId,$response)){
                $this->_redirectToCartAndShowError('Invalid response from SagePay, please contact our support team to rectify payment.');
            }else{
                return $response;
            }
        }
    }

    public function decrypt($strIn) {
        $cryptPass = $this->_config->getFormEncryptedPassword();

        //** remove the first char which is @ to flag this is AES encrypted
        $strIn = substr($strIn, 1);

        //** HEX decoding
        $strIn = pack('H*', $strIn);

        $this->_crypt->setBlockLength(128);
        $this->_crypt->setKey($cryptPass);
        $this->_crypt->setIV($cryptPass);
        $decoded = $this->_crypt->decrypt($strIn);

        return $decoded;
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

}
