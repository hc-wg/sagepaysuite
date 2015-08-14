<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Model;

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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

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
         return parent::order($payment, $amount);
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
        return parent::void($payment);
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
        return parent::capture($payment, $amount);
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
        return parent::refund($payment, $amount);
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return parent::cancel($payment);
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
    }

    /**
     * Assign data to info model instance
     *
     * @param array|\Magento\Framework\Object $data
     * @return \Magento\Payment\Model\Info
     */
    public function assignData($data)
    {
        return parent::assignData($data);
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

    }

    /**
     * Check void availability
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @internal param \Magento\Framework\Object $payment
     */
    public function canVoid()
    {
        return $this->_canVoid;
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture()
    {
        return $this->_canCapture;
    }

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
