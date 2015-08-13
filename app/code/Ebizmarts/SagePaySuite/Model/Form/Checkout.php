<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model\Form;

use Magento\Customer\Api\Data\CustomerInterface as CustomerDataObject;
use Magento\Customer\Model\AccountManagement;
use Ebizmarts\SagePaySuite\Model\Config as SuiteConfig;
use Ebizmarts\SagePaySuite\Model\Form\Checkout\Quote as SagePaySuiteQuote;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Quote\Model\Quote\Address;

/**
 * Wrapper that performs SagePaySuite FORM and Checkout communication
 * Use current FORM method instance
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Checkout
{
    /**
     * Cache ID prefix for "pal" lookup
     * @var string
     */
    //const PAL_CACHE_ID = 'paypal_express_checkout_pal';

    /**
     * Keys for passthrough variables in sales/quote_payment and sales/order_payment
     * Uses additional_information as storage
     */
//    const PAYMENT_INFO_TRANSPORT_TOKEN    = 'paypal_express_checkout_token';
//    const PAYMENT_INFO_TRANSPORT_SHIPPING_OVERRIDDEN = 'paypal_express_checkout_shipping_overridden';
//    const PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD = 'paypal_express_checkout_shipping_method';
//    const PAYMENT_INFO_TRANSPORT_PAYER_ID = 'paypal_express_checkout_payer_id';
//    const PAYMENT_INFO_TRANSPORT_REDIRECT = 'paypal_express_checkout_redirect_required';
//    const PAYMENT_INFO_TRANSPORT_BILLING_AGREEMENT = 'paypal_ec_create_ba';

    /**
     * Flag which says that was used PayPal Express Checkout button for checkout
     * Uses additional_information as storage
     * @var string
     */
    //const PAYMENT_INFO_BUTTON = 'button';

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * Config instance
     *
     * @var SuiteConfig
     */
    protected $_config;

    /**
     * API instance
     *
     * @var \Magento\Paypal\Model\Api\Nvp
     */
    //protected $_api;

    /**
     * Api Model Type
     *
     * @var string
     */
    //protected $_apiType = 'Magento\Paypal\Model\Api\Nvp';

    /**
     * Payment method type
     *
     * @var string
     */
    protected $_methodType = SuiteConfig::METHOD_FORM;

    /**
     * Redirect URL
     *
     * @var string
     */
    protected $_redirectUrl = '';

    /**
     * Crypt
     *
     * @var string
     */
    protected $_crypt = '';

    /**
     * State helper variable
     *
     * @var string
     */
    protected $_pendingPaymentMessage = '';

    /**
     * State helper variable
     *
     * @var string
     */
    protected $_checkoutRedirectUrl = '';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Create Billing Agreement flag
     *
     * @var bool
     */
    //protected $_isBARequested = false;

    /**
     * Flag for Bill Me Later mode
     *
     * @var bool
     */
    //protected $_isBml = false;

    /**
     * Customer ID
     *
     * @var int
     */
    protected $_customerId;

    /**
     * Billing agreement that might be created during order placing
     *
     * @var \Magento\Paypal\Model\Billing\Agreement
     */
    //protected $_billingAgreement;

    /**
     * Order
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Magento\Framework\App\Cache\Type\Config
     */
    protected $_configCacheType;

    /**
     * Checkout data
     *
     * @var \Magento\Checkout\Helper\Data
     */
    protected $_checkoutData;

    /**
     * Tax data
     *
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxData;

    /**
     * Customer data
     *
     * @var \Magento\Customer\Model\Url
     */
    protected $_customerUrl;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Info
     */
    protected $_sagepayInfo;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_coreUrl;

    /**
     * @var \Magento\Checkout\Model\Type\OnepageFactory
     */
    protected $_checkoutOnepageFactory;

    /**
     * @var \Magento\Paypal\Model\Billing\AgreementFactory
     */
    //protected $_agreementFactory;

    /**
     * @var \Magento\Paypal\Model\Api\Type\Factory
     */
    //protected $_apiTypeFactory;

    /**
     * @var \Magento\Framework\Object\Copy
     */
    protected $_objectCopyService;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Customer\Model\AccountManagement
     */
    protected $_accountManagement;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var SagePaySuiteQuote
     */
    protected $sagepaysuiteQuote;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Ebizmarts\SagePaySuite\Model\Info $sagepayInfo
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $coreUrl
     * @param \Magento\Checkout\Model\Type\OnepageFactory $onepageFactory
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Framework\Object\Copy $objectCopyService
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param AccountManagement $accountManagement
     * @param SagePaySuiteQuote $sagepaysuiteQuote
     * @param OrderSender $orderSender
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param array $params
     * @throws \Exception
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Ebizmarts\SagePaySuite\Model\Info $sagepayInfo,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $coreUrl,
        \Magento\Checkout\Model\Type\OnepageFactory $onepageFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\Object\Copy $objectCopyService,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        AccountManagement $accountManagement,
        SagePaySuiteQuote $sagepaysuiteQuote,
        OrderSender $orderSender,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        $params = []
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->_customerUrl = $customerUrl;
        $this->_taxData = $taxData;
        $this->_checkoutData = $checkoutData;
        $this->_configCacheType = $configCacheType;
        $this->_logger = $logger;
        $this->_localeResolver = $localeResolver;
        $this->_sagepayInfo = $sagepayInfo;
        $this->_storeManager = $storeManager;
        $this->_coreUrl = $coreUrl;
        //$this->_cartFactory = $cartFactory;
        $this->_checkoutOnepageFactory = $onepageFactory;
        //$this->_agreementFactory = $agreementFactory;
        //$this->_apiTypeFactory = $apiTypeFactory;
        $this->_objectCopyService = $objectCopyService;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerRepository = $customerRepository;
        $this->_encryptor = $encryptor;
        $this->_messageManager = $messageManager;
        $this->orderSender = $orderSender;
        $this->_accountManagement = $accountManagement;
        $this->sagepaysuiteQuote = $sagepaysuiteQuote;
        $this->quoteRepository = $quoteRepository;
        $this->_customerSession = isset($params['session'])
            && $params['session'] instanceof \Magento\Customer\Model\Session ? $params['session'] : $customerSession;

        if (isset($params['config']) && $params['config'] instanceof SuiteConfig) {
            $this->_config = $params['config'];
        } else {
            throw new \Exception('Config instance is required.');
        }

        if (isset($params['quote']) && $params['quote'] instanceof \Magento\Quote\Model\Quote) {
            $this->_quote = $params['quote'];
        } else {
            throw new \Exception('Quote instance is required.');
        }
    }

    /**
     * Setter for customer
     *
     * @param CustomerDataObject $customerData
     * @return $this
     */
    public function setCustomerData(CustomerDataObject $customerData)
    {
        $this->_quote->assignCustomer($customerData);
        $this->_customerId = $customerData->getId();
        return $this;
    }

    /**
     * Setter for customer with billing and shipping address changing ability
     *
     * @param CustomerDataObject $customerData
     * @param Address|null $billingAddress
     * @param Address|null $shippingAddress
     * @return $this
     */
    public function setCustomerWithAddressChange(
        CustomerDataObject $customerData,
        $billingAddress = null,
        $shippingAddress = null
    ) {
        $this->_quote->assignCustomerWithAddressChange($customerData, $billingAddress, $shippingAddress);
        $this->_customerId = $customerData->getId();
        return $this;
    }

    /**
     * Reserve order ID for specified quote and start checkout on PayPal
     *
     * @param string $returnUrl
     * @param string $cancelUrl
     * @param bool|null $button
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function start($successUrl, $failureUrl)
    {
        $this->_quote->collectTotals();

        if (!$this->_quote->getGrandTotal()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'SagePay can\'t process orders with a zero balance due. '
                    . 'To finish your purchase, please go through the standard checkout process.'
                )
            );
        }

        //save order pre payment
        //$this->placeBefore();

        $this->_quote->reserveOrderId();
        $this->quoteRepository->save($this->_quote);

        // suppress or export shipping address
//        if ($this->_quote->getIsVirtual()) {
//
//        } else {
//            $address = $this->_quote->getShippingAddress();
//            $isOverridden = 0;
//            if (true === $address->validate()) {
//                $isOverridden = 1;
//            }
//            $this->_quote->getPayment()->save();
//        }

        $this->_crypt = $this->makeCrypt($successUrl, $failureUrl);
        $this->_redirectUrl = $this->_config->getSagePayFormUrl($this->_config->getMode(),SuiteConfig::ACTION_POST);
    }

    protected function makeCrypt($successUrl, $failureUrl) {

        $encrypted_password = $this->_config->getFormEncryptedPassword();

        if(empty($encrypted_password)){
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid FORM encrypted password.'));
        }

        $billing_address = $this->_quote->getBillingAddress();
        $shipping_address = $this->_quote->getShippingAddress();

        //if billing is empty
        if(is_null($billing_address->getCountry())){
            $billing_address = $shipping_address;
            $this->_quote->setBillingAddress($billing_address);
        }

        $customer_data = $this->_customerSession->getCustomerDataObject();

        $data = array();
        //mandatory
        $data['VendorTxCode'] = substr(date('Y-m-d-H-i-s-') . time(), 0, 40);
        $data['Amount'] = $this->_quote->getGrandTotal();
        $data['Currency'] = $this->_quote->getQuoteCurrencyCode();
        $data['Description'] = "description";
        $data['SuccessURL'] = $successUrl;
        $data['FailureURL'] = $failureUrl;

        //not mandatory
//        $data['CustomerName'] = $billing_address->getFirstname() . ' ' . $billing_address->getLastname();
//        $data['CustomerEMail'] = ($customerEmail == null ? $billing->getEmail() : $customerEmail);
//        $data['VendorEMail']
//        $data['SendEMail']
//        $data['EmailMessage']

        //mandatory
        $data['BillingSurname']    = substr($billing_address->getLastname(), 0, 20);
        $data['BillingFirstnames'] = substr($billing_address->getFirstname(), 0, 20);
        $data['BillingAddress1']   = substr($billing_address->getStreetLine(1), 0, 100);
        $data['BillingCity']       = substr($billing_address->getCity(), 0,  40);
        $data['BillingPostCode']   = substr($billing_address->getPostcode(), 0, 10);
        $data['BillingCountry']    = substr($billing_address->getCountry(), 0, 2);
        $data['BillingState'] = substr($billing_address->getRegionCode(), 0, 2);

        //not mandatory
//        $data['BillingAddress2']   = ($this->getConfigData('mode') == 'test') ? 88 : $this->ss($billing->getStreet(2), 100);


        //mandatory
        $data['DeliverySurname']    = substr($shipping_address->getLastname(), 0, 20);
        $data['DeliveryFirstnames'] = substr($shipping_address->getFirstname(), 0, 20);
        $data['DeliveryAddress1']   = substr($shipping_address->getStreetLine(1), 0, 100);
        $data['DeliveryCity']       = substr($shipping_address->getCity(), 0,  40);
        $data['DeliveryPostCode']   = substr($shipping_address->getPostcode(), 0, 10);
        $data['DeliveryCountry']    = substr($shipping_address->getCountry(), 0, 2);
        $data['DeliveryState'] = substr($shipping_address->getRegionCode(), 0, 2);

        //not mandatory
//        $data['DeliveryAddress2']   = ($this->getConfigData('mode') == 'test') ? 88 : $this->ss($billing->getStreet(2), 100);
//        $data['DeliveryState'] = $billing->getRegionCode();
//        $data['DeliveryPhone'] = $billing->getRegionCode();

//        $data['BasketXML'] = $basket;
//        $data['AllowGiftAid'] = (int)$this->getConfigData('allow_gift_aid');
//        $data['ApplyAVSCV2']  = $this->getConfigData('avscv2');
//        $data['Apply3DSecure']  = $this->getConfigData('avscv2');
//        $data['BillingAgreement']  = $this->getConfigData('avscv2');
//        $data['BasketXML']  = $this->getConfigData('avscv2');
//        $data['CustomerXML']  = $this->getConfigData('avscv2');
//        $data['SurchargeXML']  = $this->getConfigData('avscv2');
//        $data['VendorData']  = $this->getConfigData('avscv2');
//        $data['ReferrerID']        = $this->getConfigData('referrer_id');
//        $data['Website']        = $this->getConfigData('referrer_id');

        $preCryptString = '';
        foreach ($data as $field => $value) {
            if ($value != '') {
                $preCryptString .= ($preCryptString == '') ? "$field=$value" : "&$field=$value";
            }
        }

        ksort($data);

        //** add PKCS5 padding to the text to be encypted
        $pkcs5Data = $this->_addPKCS5Padding($preCryptString);

        $crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $encrypted_password, $pkcs5Data, MCRYPT_MODE_CBC, $encrypted_password);

        return "@" . bin2hex($crypt);
    }

    //** PHP's mcrypt does not have built in PKCS5 Padding, so we use this
    protected function _addPKCS5Padding($input) {
        $blocksize = 16;
        $padding = "";

        // Pad input to an even block size boundary
        $padlength = $blocksize - (strlen($input) % $blocksize);
        for ($i = 1; $i <= $padlength; $i++) {
            $padding .= chr($padlength);
        }

        return $input . $padding;
    }

    /**
     * Update quote when returned from PayPal
     * rewrite billing address by paypal
     * save old billing address for new customer
     * export shipping address in case address absence
     *
     * @param string $token
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function returnFromSagePay()
    {

        $quote = $this->_quote;

        //hardcode shipping address for now
        $sa = $quote->getShippingAddress();
        $ba = $quote->getBillingAddress();
        $ba->setFirstname($sa->getFirstname());
        $ba->setLastname($sa->getLastname());
        $ba->setStreet($sa->getStreet());
        $ba->setTelephone($sa->getTelephone());
        $ba->setCity($sa->getCity());
        $ba->setPostcode($sa->getPostcode());
        $ba->setCountryId($sa->getCountryId());
        $ba->setRegionCode($sa->getRegionCode());
        $ba->save();
        $quote->setBillingAddress($ba);

        // import payment info
        $payment = $quote->getPayment();
        $payment->setMethod($this->_methodType);

//        $order = $this->_order;
//        $order->addPayment($payment);

        $quote->collectTotals();
        $this->quoteRepository->save($quote);
    }

    /**
     * Check whether order review has enough data to initialize
     *
     * @param string|null $token
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function prepareOrderReview($token = null)
    {
        $this->_ignoreAddressValidation();
        $this->_quote->collectTotals();
        $this->quoteRepository->save($this->_quote);
    }

    /**
     * Set shipping method to quote, if needed
     *
     * @param string $methodCode
     * @return void
     */
    public function updateShippingMethod($methodCode)
    {
        $shippingAddress = $this->_quote->getShippingAddress();
        if (!$this->_quote->getIsVirtual() && $shippingAddress) {
            if ($methodCode != $shippingAddress->getShippingMethod()) {
                $this->_ignoreAddressValidation();
                $shippingAddress->setShippingMethod($methodCode)->setCollectShippingRates(true);
                $this->_quote->collectTotals();
                $this->quoteRepository->save($this->_quote);
            }
        }
    }

    /**
     * Place the order when customer returned from SagePay until this moment all quote data must be valid.
     *
     * @param string $token
     * @param string|null $shippingMethodCode
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function place($shippingMethodCode = null)
    {
        if ($shippingMethodCode) {
            $this->updateShippingMethod($shippingMethodCode);
        }

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

        $this->_ignoreAddressValidation();
        $this->_quote->collectTotals();

        $order = $this->quoteManagement->submit($this->_quote);

        if ($isNewCustomer) {
            try {
                $this->_involveNewCustomer();
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
        if (!$order) {
            return;
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
        $this->_order = $order;
    }

    public function placeBefore($shippingMethodCode = null)
    {
        if ($shippingMethodCode) {
            $this->updateShippingMethod($shippingMethodCode);
        }

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

        $this->_ignoreAddressValidation();
        $this->_quote->collectTotals();

        $order = $this->quoteManagement->submit($this->_quote);

        if ($isNewCustomer) {
            try {
                $this->_involveNewCustomer();
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
        if (!$order) {
            return;
        }

        $state = $order->getState();

        $this->_order = $order;
    }

    /**
     * Make sure addresses will be saved without validation errors
     *
     * @return void
     */
    private function _ignoreAddressValidation()
    {
        $this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$this->_quote->getIsVirtual()) {
            $this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);
            $this->_quote->getBillingAddress()->setSameAsBilling(1);
        }
    }

    /**
     * Determine whether redirect somewhere specifically is required
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->_redirectUrl;
    }

    /**
     * Get FORM crypt
     *
     * @return string
     */
    public function getFormCrypt()
    {
        return $this->_crypt;
    }

    /**
     * Return order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Get checkout method
     *
     * @return string
     */
    public function getCheckoutMethod()
    {
        if ($this->getCustomerSession()->isLoggedIn()) {
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
            ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
        return $this;
    }

    /**
     * Prepare quote for customer registration and customer order submit
     * and restore magento customer data from quote
     *
     * @return void
     */
    protected function _prepareNewCustomerQuote()
    {
        $this->sagepaysuiteQuote->prepareQuoteForNewCustomer($this->_quote);
    }

    /**
     * Prepare quote for customer order submit
     *
     * @return void
     */
    protected function _prepareCustomerQuote()
    {
        $this->sagepaysuiteQuote->prepareRegisteredCustomerQuote($this->_quote, $this->_customerSession->getCustomerId());
    }

    /**
     * Involve new customer to system
     *
     * @return $this
     */
    protected function _involveNewCustomer()
    {
        $customer = $this->_quote->getCustomer();
        $confirmationStatus = $this->_accountManagement->getConfirmationStatus($customer->getId());
        if ($confirmationStatus === AccountManagement::ACCOUNT_CONFIRMATION_REQUIRED) {
            $url = $this->_customerUrl->getEmailConfirmationUrl($customer->getEmail());
            $this->_messageManager->addSuccess(
            // @codingStandardsIgnoreStart
                __('Account confirmation is required. Please check your email for confirmation link. To resend confirmation email please <a href="%1">click here</a>.', $url)
            // @codingStandardsIgnoreEnd
            );
        } else {
            $this->getCustomerSession()->regenerateId();
            $this->getCustomerSession()->loginById($customer->getId());
        }
        return $this;
    }

    /**
     * Get customer session object
     *
     * @return \Magento\Customer\Model\Session
     */
    public function getCustomerSession()
    {
        return $this->_customerSession;
    }
}
