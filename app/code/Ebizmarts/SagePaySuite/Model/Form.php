<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Model;

//use Magento\Paypal\Model\Api\Nvp;
use Ebizmarts\SagePaySuite\Model\Api\ProcessableException as ApiProcessableException;
//use Magento\Paypal\Model\Express\Checkout as ExpressCheckout;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;

/**
 * SagePaySuite FORM Module
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Form extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = \Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM;

    /**
     * @var string
     */
    protected $_formBlockType = 'Ebizmarts\SagePaySuite\Block\Form\Form';

    /**
     * @var string
     */
    protected $_infoBlockType = 'Ebizmarts\SagePaySuite\Block\Payment\Info';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canOrder = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canReviewPayment = true;

    /**
     * Suite processing instance
     *
     * @var \Ebizmarts\SagePaySuite\Model\Suite
     */
    protected $_suite;

    /**
     * Payment additional information key for payment action
     *
     * @var string
     */
    //protected $_isOrderPaymentActionKey = 'is_order_action';

    /**
     * Payment additional information key for number of used authorizations
     *
     * @var string
     */
    //protected $_authorizationCountKey = 'authorization_count';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Paypal\Model\CartFactory
     */
    //protected $_cartFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\Exception\LocalizedExceptionFactory
     */
    protected $_exception;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param ProFactory $proFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param CartFactory $cartFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Exception\LocalizedExceptionFactory $exception
     * @param \Magento\Framework\Model\Resource\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        SuiteFactory $suiteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        //\Magento\Paypal\Model\CartFactory $cartFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Exception\LocalizedExceptionFactory $exception,
        \Magento\Framework\Model\Resource\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $urlBuilder;
        //$this->_cartFactory = $cartFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_exception = $exception;

        $suiteInstance = array_shift($data);
        if ($suiteInstance && $suiteInstance instanceof \Ebizmarts\SagePaySuite\Model\Suite) {
            $this->_suite = $suiteInstance;
        } else {
            $this->_suite = $suiteFactory->create();
        }
        $this->_suite->setMethod($this->_code);
        $this->_setApiProcessableErrors();
    }

    /**
     * Set processable error codes to API model
     *
     * @return \Magento\Paypal\Model\Api\Nvp
     */
    protected function _setApiProcessableErrors()
    {
//        return $this->_pro->getApi()->setProcessableErrors(
//            [
//                ApiProcessableException::API_INVALID_IP
//            ]
//        );
    }

    /**
     * Store setter
     * Also updates store ID in config object
     *
     * @param \Magento\Store\Model\Store|int $store
     * @return $this
     */
    public function setStore($store)
    {
        $this->setData('store', $store);
        if (null === $store) {
            $store = $this->_storeManager->getStore()->getId();
        }
        $this->_suite->getConfig()->setStoreId(is_object($store) ? $store->getId() : $store);
        return $this;
    }

    /**
     * Can be used in regular checkout
     *
     * @return bool
     */
    public function canUseCheckout()
    {
//        if ($this->_scopeConfig->isSetFlag(
//                'payment/hosted_pro/active',
//                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
//            ) && !$this->_scopeConfig->isSetFlag(
//                'payment/hosted_pro/display_ec',
//                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
//            )
//        ) {
//            return false;
//        }
        return parent::canUseCheckout();
    }

    /**
     * Whether method is available for specified currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->_suite->getConfig()->isCurrencyCodeSupported($currencyCode);
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @see \Magento\Sales\Model\Payment::place()
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return $this->_suite->getConfig()->getPaymentAction();
    }

    /**
     * Check whether payment method can be used
     * @param Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (parent::isAvailable($quote) && $this->_suite->getConfig()->isMethodAvailable()) {
            return true;
        }
        return false;
    }

    /**
     * Custom getter for payment configuration
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConfigData($field, $storeId = null)
    {
        return $this->_suite->getConfig()->getValue($field);
    }

    /**
     * Order payment
     *
     * @param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
//        $sagepayTransactionData = $this->_checkoutSession->getSagePayTransactionData();
//        if (!is_array($sagepayTransactionData)) {
//            $this->_placeOrder($payment, $amount);
//        } else {
//            //$this->_importToPayment($this->_pro->getApi()->setData($paypalTransactionData), $payment);
//        }
//
//        //$payment->setAdditionalInformation($this->_isOrderPaymentActionKey, true);
//
//        if ($payment->getIsFraudDetected()) {
//            return $this;
//        }
//
//        $order = $payment->getOrder();
//        $orderTransactionId = $payment->getTransactionId();
//
//        $api = $this->_callDoAuthorize($amount, $payment, $orderTransactionId);
//
//        $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
//        $status = true;
//
//        $formattedPrice = $order->getBaseCurrency()->formatTxt($amount);
//        if ($payment->getIsTransactionPending()) {
//            $message = __('The ordering amount of %1 is pending approval on the payment gateway.', $formattedPrice);
//            $state = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
//        } else {
//            $message = __('Ordered amount of %1', $formattedPrice);
//        }
//
//        $payment->addTransaction(Transaction::TYPE_ORDER, null, false, $message);
//
//        $this->_pro->importPaymentInfo($api, $payment);
//
//        if ($payment->getIsTransactionPending()) {
//            $message = __(
//                'We\'ll authorize the amount of %1 as soon as the payment gateway approves it.',
//                $formattedPrice
//            );
//            $state = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
//            if ($payment->getIsFraudDetected()) {
//                $status = \Magento\Sales\Model\Order::STATUS_FRAUD;
//            }
//        } else {
//            $message = __('The authorized amount is %1.', $formattedPrice);
//        }
//
//        $payment->resetTransactionAdditionalInfo();
//
//        $payment->setTransactionId($api->getTransactionId());
//        $payment->setParentTransactionId($orderTransactionId);
//
//        $payment->addTransaction(Transaction::TYPE_AUTH, null, false, $message);
//
//        $order->setState($state)
//            ->setStatus($status);
//
//        $payment->setSkipOrderProcessing(true);
         return $this;
    }

    /**
     * Authorize payment
     *
     * @param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this->_placeOrder($payment, $amount);
    }

    /**
     * Void payment
     *
     * @param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
//        //Switching to order transaction if needed
//        if ($payment->getAdditionalInformation(
//                $this->_isOrderPaymentActionKey
//            ) && !$payment->getVoidOnlyAuthorization()
//        ) {
//            $orderTransaction = $payment->lookupTransaction(false, Transaction::TYPE_ORDER);
//            if ($orderTransaction) {
//                $payment->setParentTransactionId($orderTransaction->getTxnId());
//                $payment->setTransactionId($orderTransaction->getTxnId() . '-void');
//            }
//        }
//        $this->_pro->void($payment);
        return $this;
    }

    /**
     * Capture payment
     *
     * @param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
//        $authorizationTransaction = $payment->getAuthorizationTransaction();
//        $authorizationPeriod = abs(intval($this->getConfigData('authorization_honor_period')));
//        $maxAuthorizationNumber = abs(intval($this->getConfigData('child_authorization_number')));
//        $order = $payment->getOrder();
//        $isAuthorizationCreated = false;
//
//        if ($payment->getAdditionalInformation($this->_isOrderPaymentActionKey)) {
//            $voided = false;
//            if (!$authorizationTransaction->getIsClosed() && $this->_isTransactionExpired(
//                    $authorizationTransaction,
//                    $authorizationPeriod
//                )
//            ) {
//                //Save payment state and configure payment object for voiding
//                $isCaptureFinal = $payment->getShouldCloseParentTransaction();
//                $payment->setShouldCloseParentTransaction(false);
//                $payment->setParentTransactionId($authorizationTransaction->getTxnId());
//                $payment->unsTransactionId();
//                $payment->setVoidOnlyAuthorization(true);
//                $payment->void(new \Magento\Framework\Object());
//
//                //Revert payment state after voiding
//                $payment->unsAuthorizationTransaction();
//                $payment->unsTransactionId();
//                $payment->setShouldCloseParentTransaction($isCaptureFinal);
//                $voided = true;
//            }
//
//            if ($authorizationTransaction->getIsClosed() || $voided) {
//                if ($payment->getAdditionalInformation($this->_authorizationCountKey) > $maxAuthorizationNumber - 1) {
//                    $this->_exception->create(
//                        ['phrase' => __('The maximum number of child authorizations is reached.')]
//                    );
//                }
//                $api = $this->_callDoAuthorize($amount, $payment, $authorizationTransaction->getParentTxnId());
//
//                //Adding authorization transaction
//                $this->_pro->importPaymentInfo($api, $payment);
//                $payment->setTransactionId($api->getTransactionId());
//                $payment->setParentTransactionId($authorizationTransaction->getParentTxnId());
//                $payment->setIsTransactionClosed(false);
//
//                $formatedPrice = $order->getBaseCurrency()->formatTxt($amount);
//
//                if ($payment->getIsTransactionPending()) {
//                    $message = __(
//                        'We\'ll authorize the amount of %1 as soon as the payment gateway approves it.',
//                        $formatedPrice
//                    );
//                } else {
//                    $message = __('The authorized amount is %1.', $formatedPrice);
//                }
//
//                $transaction = $payment->addTransaction(Transaction::TYPE_AUTH, null, true, $message);
//
//                $payment->setParentTransactionId($api->getTransactionId());
//                $isAuthorizationCreated = true;
//            }
//            //close order transaction if needed
//            if ($payment->getShouldCloseParentTransaction()) {
//                $orderTransaction = $payment->lookupTransaction(false, Transaction::TYPE_ORDER);
//
//                if ($orderTransaction) {
//                    $orderTransaction->setIsClosed(true);
//                    $order->addRelatedObject($orderTransaction);
//                }
//            }
//        }
//
//        if (false === $this->_pro->capture($payment, $amount)) {
//            $this->_placeOrder($payment, $amount);
//        }
//
//        if ($isAuthorizationCreated && isset($transaction)) {
//            $transaction->setIsClosed(true);
//        }

        return $this;
    }

    /**
     * Refund capture
     *
     * @param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
//        $this->_pro->refund($payment, $amount);
        return $this;
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
//        $this->void($payment);

        return $this;
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see Quote\Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('sagepaysuite/form/start');
    }

    /**
     * Fetch transaction details info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {
        //return $this->_pro->fetchTransactionInfo($payment, $transactionId);
    }

    /**
     * @return Api\Nvp
     */
//    public function getApi()
//    {
//        return $this->_pro->getApi();
//    }

    /**
     * Assign data to info model instance
     *
     * @param array|\Magento\Framework\Object $data
     * @return \Magento\Payment\Model\Info
     */
    public function assignData($data)
    {
        $result = parent::assignData($data);
//        $key = ExpressCheckout::PAYMENT_INFO_TRANSPORT_BILLING_AGREEMENT;
//        if (is_array($data)) {
//            $this->getInfoInstance()->setAdditionalInformation($key, isset($data[$key]) ? $data[$key] : null);
//        } elseif ($data instanceof \Magento\Framework\Object) {
//            $this->getInfoInstance()->setAdditionalInformation($key, $data->getData($key));
//        }
        return $result;
    }

    /**
     * Place an order with authorization or capture action
     *
     * @param Payment $payment
     * @param float $amount
     * @return $this
     */
    protected function _placeOrder(Payment $payment, $amount)
    {
        $order = $payment->getOrder();

//        // prepare api call
//        $token = $payment->getAdditionalInformation(ExpressCheckout::PAYMENT_INFO_TRANSPORT_TOKEN);
//
//        $cart = $this->_cartFactory->create(['salesModel' => $order]);
//
//        $api = $this->getApi()->setToken(
//            $token
//        )->setPayerId(
//                $payment->getAdditionalInformation(ExpressCheckout::PAYMENT_INFO_TRANSPORT_PAYER_ID)
//            )->setAmount(
//                $amount
//            )->setPaymentAction(
//                $this->_pro->getConfig()->getValue('paymentAction')
//            )->setNotifyUrl(
//                $this->_urlBuilder->getUrl('paypal/ipn/')
//            )->setInvNum(
//                $order->getIncrementId()
//            )->setCurrencyCode(
//                $order->getBaseCurrencyCode()
//            )->setPaypalCart(
//                $cart
//            )->setIsLineItemsEnabled(
//                $this->_pro->getConfig()->getValue('lineItemsEnabled')
//            );
//        if ($order->getIsVirtual()) {
//            $api->setAddress($order->getBillingAddress())->setSuppressShipping(true);
//        } else {
//            $api->setAddress($order->getShippingAddress());
//            $api->setBillingAddress($order->getBillingAddress());
//        }
//
//        // call api and get details from it
//        $api->callDoExpressCheckoutPayment();
//
//        $this->_importToPayment($api, $payment);
        return $this;
    }

    /**
     * Import payment info to payment
     *
     * @param Nvp $api
     * @param Payment $payment
     * @return void
     */
//    protected function _importToPayment($api, $payment)
//    {
//        $payment->setTransactionId(
//            $api->getTransactionId()
//        )->setIsTransactionClosed(
//                0
//            )->setAdditionalInformation(
//                ExpressCheckout::PAYMENT_INFO_TRANSPORT_REDIRECT,
//                $api->getRedirectRequired()
//            );
//
//        if ($api->getBillingAgreementId()) {
//            $payment->setBillingAgreementData(
//                [
//                    'billing_agreement_id' => $api->getBillingAgreementId(),
//                    'method_code' => \Magento\Paypal\Model\Config::METHOD_BILLING_AGREEMENT,
//                ]
//            );
//        }
//
//        $this->_pro->importPaymentInfo($api, $payment);
//    }

    /**
     * Check void availability
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @internal param \Magento\Framework\Object $payment
     */
    public function canVoid()
    {
//        $info = $this->getInfoInstance();
//        if ($info->getAdditionalInformation($this->_isOrderPaymentActionKey)) {
//            $orderTransaction = $info->lookupTransaction(false, Transaction::TYPE_ORDER);
//            if ($orderTransaction) {
//                $info->setParentTransactionId($orderTransaction->getTxnId());
//            }
//        }

        return $this->_canVoid;
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture()
    {
//        $payment = $this->getInfoInstance();
//        $this->_pro->getConfig()->setStoreId($payment->getOrder()->getStore()->getId());
//
//        if ($payment->getAdditionalInformation($this->_isOrderPaymentActionKey)) {
//            $orderTransaction = $payment->lookupTransaction(false, Transaction::TYPE_ORDER);
//            if ($orderTransaction->getIsClosed()) {
//                return false;
//            }
//
//            $orderValidPeriod = abs(intval($this->getConfigData('order_valid_period')));
//
//            $dateCompass = new \DateTime($orderTransaction->getCreatedAt());
//            $dateCompass->modify('+' . $orderValidPeriod . ' days');
//            $currentDate = new \DateTime();
//
//            if ($currentDate > $dateCompass || $orderValidPeriod == 0) {
//                return false;
//            }
//        }
        return $this->_canCapture;
    }

    /**
     * Call DoAuthorize
     *
     * @param int $amount
     * @param \Magento\Framework\Object $payment
     * @param string $parentTransactionId
     * @return \Magento\Paypal\Model\Api\AbstractApi
     */
//    protected function _callDoAuthorize($amount, $payment, $parentTransactionId)
//    {
//        $apiData = $this->_pro->getApi()->getData();
//        foreach ($apiData as $k => $v) {
//            if (is_object($v)) {
//                unset($apiData[$k]);
//            }
//        }
//        $this->_checkoutSession->setPaypalTransactionData($apiData);
//        $this->_pro->resetApi();
//        $api = $this->_setApiProcessableErrors()
//            ->setAmount($amount)
//            ->setCurrencyCode($payment->getOrder()->getBaseCurrencyCode())
//            ->setTransactionId($parentTransactionId)
//            ->callDoAuthorization();
//
//        $payment->setAdditionalInformation(
//            $this->_authorizationCountKey,
//            $payment->getAdditionalInformation($this->_authorizationCountKey) + 1
//        );
//
//        return $api;
//    }

    /**
     * Check transaction for expiration in PST
     *
     * @param Transaction $transaction
     * @param int $period
     * @return bool
     */
//    protected function _isTransactionExpired(Transaction $transaction, $period)
//    {
//        $period = intval($period);
//        if (0 == $period) {
//            return true;
//        }
//
//        $transactionClosingDate = new \DateTime($transaction->getCreatedAt(), new \DateTimeZone('GMT'));
//        $transactionClosingDate->setTimezone(new \DateTimeZone('US/Pacific'));
//        /**
//         * 11:49:00 PayPal transactions closing time
//         */
//        $transactionClosingDate->setTime(11, 49, 00);
//        $transactionClosingDate->modify('+' . $period . ' days');
//
//        $currentTime = new \DateTime(null, new \DateTimeZone('US/Pacific'));
//
//        if ($currentTime > $transactionClosingDate) {
//            return true;
//        }
//
//        return false;
//    }

    /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        $pathForm = 'payment/' . Config::METHOD_FORM . '/active';

        return parent::isActive($storeId)
        || (bool)(int)$this->_scopeConfig->getValue($pathForm, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function decrypt($strIn) {
        $cryptPass = $this->_suite->getConfig()->getFormEncryptedPassword();

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
}
