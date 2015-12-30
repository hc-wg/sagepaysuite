<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Customer\Api\Data\GroupInterface;

class Success extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * Checkout data
     *
     * @var \Magento\Checkout\Helper\Data
     */
    protected $_checkoutData;

    protected $_quote;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var OrderSender
     */
    protected $orderSender;

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
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $_objectCopyService;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $_dataObjectHelper;

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
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        OrderSender $orderSender,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\DataObject\Copy $objectCopyService
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->_checkoutData = $checkoutData;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->_logger = $logger;
        $this->_transactionFactory = $transactionFactory;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_objectCopyService = $objectCopyService;
        $this->_customerRepository = $customerRepository;
        $this->_dataObjectHelper = $dataObjectHelper;
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
            $payment->setCcExpMonth(substr($response["ExpiryDate"],0,2));
            $payment->setCcExpYear(substr($response["ExpiryDate"],2));
            $payment->setAdditionalInformation('statusDetail', $response["StatusDetail"]);
            $payment->setAdditionalInformation('vendorTxCode', $response["VendorTxCode"]);

            $order = $this->placeOrder();

            //prepare session to success or cancellation page
            $this->_getCheckoutSession()->clearHelperData();

            //set last successful quote
            $quoteId = $this->_quote->getId();
            $this->_getCheckoutSession()->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

            //an order may be created
            if ($order) {
                $this->_getCheckoutSession()->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
            }

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setIsTransactionClosed(1);
            $payment->setCcType($response["CardType"]);
            $payment->setCcLast4($response["Last4Digits"]);
            $payment->setCcExpMonth(substr($response["ExpiryDate"],0,2));
            $payment->setCcExpYear(substr($response["ExpiryDate"],2));
            $payment->setAdditionalInformation('statusDetail', $response["StatusDetail"]);
            $payment->setAdditionalInformation('vendorTxCode', $response["VendorTxCode"]);
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

        return $this->removePKCS5Padding(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $cryptPass, $strIn, MCRYPT_MODE_CBC, $cryptPass));
    }

    // Need to remove padding bytes from end of decoded string
    public function removePKCS5Padding($decrypted) {
        $padChar = ord($decrypted[strlen($decrypted) - 1]);

        return substr($decrypted, 0, -$padChar);
    }

    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
//        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    /**
     * Place the order when customer returned from SagePay until this moment all quote data must be valid.
     */
    protected function placeOrder()
    {

        $isNewCustomer = false;
        switch ($this->getCheckoutMethod()) {
            case \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST:
                $this->_prepareGuestQuote();
                break;
            case \Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER:
                $this->_prepareNewCustomerQuote();
                $isNewCustomer = true;
                break;
            default:
                $this->_prepareCustomerQuote();
                break;
        }

        //$this->_ignoreAddressValidation();
        $this->_quote->collectTotals();

        $order = $this->quoteManagement->submit($this->_quote);

//        if ($isNewCustomer) {
//            try {
//                $this->_involveNewCustomer();
//            } catch (\Exception $e) {
//                $this->_logger->critical($e);
//            }
//        }
        if (!$order) {
            return null;
        }

        switch ($order->getState()) {
            case \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT:
                // TODO
                break;
            // regular placement, when everything is ok
            case \Magento\Sales\Model\Order::STATE_PROCESSING:
            case \Magento\Sales\Model\Order::STATE_COMPLETE:
            case \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW:
                $this->orderSender->send($order);
                break;
            default:
                break;
        }
        return $order;
    }

    /**
     * Get checkout method
     *
     * @return string
     */
    public function getCheckoutMethod()
    {
        if ($this->_getCustomerSession()->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }
        if (!$this->_quote->getCheckoutMethod()) {
            if ($this->_checkoutData->isAllowedGuestCheckout($this->_quote)) {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }
        return $this->_quote->getCheckoutMethod();
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCustomerSession()
    {
        return $this->_customerSession;
//        return $this->_objectManager->get('Magento\Customer\Model\Session');
    }
    /**
     * Prepare quote for guest checkout order submit
     *
     * @return $this
     */
    protected function _prepareGuestQuote()
    {
        $quote = $this->_quote;

        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        return $this;
    }

    /**
     * Prepare quote for customer registration and customer order submit
     *
     * @return void
     */
    protected function _prepareNewCustomerQuote()
    {
        $quote = $this->_quote;
        $billing = $quote->getBillingAddress();
        $shipping = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $quote->getCustomer();
        $customerBillingData = $billing->exportCustomerAddress();
        $dataArray = $this->_objectCopyService->getDataFromFieldset('checkout_onepage_quote', 'to_customer', $quote);
        $this->_dataObjectHelper->populateWithArray(
            $customer,
            $dataArray,
            '\Magento\Customer\Api\Data\CustomerInterface'
        );
        $quote->setCustomer($customer)->setCustomerId(true);

        $customerBillingData->setIsDefaultBilling(true);

        if ($shipping) {
            if (!$shipping->getSameAsBilling()) {
                $customerShippingData = $shipping->exportCustomerAddress();
                $customerShippingData->setIsDefaultShipping(true);
                $shipping->setCustomerAddressData($customerShippingData);
                // Add shipping address to quote since customer Data Object does not hold address information
                $quote->addCustomerAddress($customerShippingData);
            } else {
                $shipping->setCustomerAddressData($customerBillingData);
                $customerBillingData->setIsDefaultShipping(true);
            }
        } else {
            $customerBillingData->setIsDefaultShipping(true);
        }
        $billing->setCustomerAddressData($customerBillingData);
        // TODO : Eventually need to remove this legacy hack
        // Add billing address to quote since customer Data Object does not hold address information
        $quote->addCustomerAddress($customerBillingData);
    }

    /**
     * Prepare quote for customer order submit
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _prepareCustomerQuote()
    {
        $quote = $this->_quote;
        $billing = $quote->getBillingAddress();
        $shipping = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $this->_customerRepository->getById($this->_getCustomerSession()->getCustomerId());
        $hasDefaultBilling = (bool)$customer->getDefaultBilling();
        $hasDefaultShipping = (bool)$customer->getDefaultShipping();

        if ($shipping && !$shipping->getSameAsBilling() &&
            (!$shipping->getCustomerId() || $shipping->getSaveInAddressBook())
        ) {
            $shippingAddress = $shipping->exportCustomerAddress();
            if (!$hasDefaultShipping) {
                //Make provided address as default shipping address
                $shippingAddress->setIsDefaultShipping(true);
                $hasDefaultShipping = true;
            }
            $quote->addCustomerAddress($shippingAddress);
            $shipping->setCustomerAddressData($shippingAddress);
        }

        if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
            $billingAddress = $billing->exportCustomerAddress();
            if (!$hasDefaultBilling) {
                //Make provided address as default shipping address
                if (!$hasDefaultShipping) {
                    //Make provided address as default shipping address
                    $billingAddress->setIsDefaultShipping(true);
                }
                $billingAddress->setIsDefaultBilling(true);
            }
            $quote->addCustomerAddress($billingAddress);
            $billing->setCustomerAddressData($billingAddress);
        }
    }

}
