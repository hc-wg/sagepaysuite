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
    protected $_infoBlockType = 'Ebizmarts\SagePaySuite\Block\Info';

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
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $_suiteHelper;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
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
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
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

        $this->_suiteHelper = $suiteHelper;
        $this->_transactionFactory = $transactionFactory;
        //$this->_messageManager = $context->getMessageManager();
    }


    /**
     * Check whether payment method can be used
     * @param Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
//        if (parent::isAvailable($quote) && $this->_suite->getConfig()->isMethodAvailable()) {
//            return true;
//        }
//        return false;
        return true;
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
        return parent::authorize($payment, $amount);
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
     * Check void availability
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @internal param \Magento\Framework\Object $payment
     */
    public function canVoid()
    {
        return $this->_canVoid;
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
