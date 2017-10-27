<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Ebizmarts\SagePaySuite\Model\Api\PIRest;

/**
 * Class PI
 */
class PI extends \Magento\Payment\Model\Method\Cc
{

    /**
     * @var string
     */
    protected $_code = Config::METHOD_PI; // @codingStandardsIgnoreLine

    protected $_formBlockType = \Ebizmarts\SagePaySuite\Block\Form\Pi::class;

    /**
     * @var string
     */
    protected $_infoBlockType = 'Ebizmarts\SagePaySuite\Block\Info'; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_isGateway = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_canAuthorize = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_canCapture = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_canCapturePartial = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_canRefund = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_canVoid = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_canUseInternal = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_canUseCheckout = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_canSaveCc = false; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true; // @codingStandardsIgnoreLine

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $config;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRest
     */
    private $_pirestapi;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared
     */
    private $_sharedApi;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @var Logger
     */
    private $_suiteLogger;

    private $_context;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param Config $config
     * @param PIRest $pirestapi
     * @param Api\Shared $sharedApi
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        PIRest $pirestapi,
        \Ebizmarts\SagePaySuite\Model\Api\Shared $sharedApi,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        Logger $suiteLogger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
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
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_context     = $context;
        $this->config       = $config;
        $this->config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_pirestapi   = $pirestapi;
        $this->_sharedApi   = $sharedApi;
        $this->_suiteHelper = $suiteHelper;
        $this->_suiteLogger = $suiteLogger;
    }

    public function assignData(DataObject $data)
    {
        parent::assignData($data);
        $infoInstance = $this->getInfoInstance();
        $infoInstance->setAdditionalInformation('cc_last4', $data->getData('cc_last4'));
        $infoInstance->setAdditionalInformation('merchant_session_key', $data->getData('merchant_session_key'));
        $infoInstance->setAdditionalInformation('card_identifier', $data->getData('card_identifier'));
        return $this;
    }

    /**
     * Set initialized flag to capture payment
     */
    public function markAsInitialized()
    {
        $this->_isInitializeNeeded = false;
    }

    /**
     * Refunds specified amount
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order        = $payment->getOrder();
            $vpsTxId      = $this->_suiteHelper->clearTransactionId($payment->getParentTransactionId());
            $vendorTxCode = $this->_suiteHelper->generateVendorTxCode($order->getIncrementId(), Config::ACTION_REFUND);
            $description  = 'Magento backend refund.';

            /** @var \Ebizmarts\SagePaySuite\Api\SagePayData\PiTransactionResultInterface $refundResult */
            $refundResult = $this->_pirestapi->refund(
                $vendorTxCode,
                $vpsTxId,
                $amount * 100,
                $order->getOrderCurrencyCode(),
                $description
            );

            $payment->setTransactionId($refundResult->getTransactionId());
            $payment->setIsTransactionClosed(1);
            $payment->setShouldCloseParentTransaction(1);
        } catch (ApiException $apiException) {
            $this->_logger->critical($apiException);
            throw new LocalizedException(
                __(
                    'There was an error refunding Sage Pay transaction %1: %2',
                    $vpsTxId,
                    $apiException->getUserMessage()
                )
            );
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new LocalizedException(
                __('There was an error refunding Sage Pay transaction %1: %2', $vpsTxId, $e->getMessage())
            );
        }

        return $this;
    }

    /**
     * Voids transaction
     *
     * @param InfoInterface $payment
     * @throws LocalizedException
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function void(InfoInterface $payment)
    {
        $transactionId = $payment->getLastTransId();

        try {
            $this->_pirestapi->void($transactionId);
        } catch (ApiException $apiException) {
            if ($this->exceptionCodeIsInvalidTransactionState($apiException)) {
                //unable to void transaction
                throw new LocalizedException(
                    __('Unable to VOID Sage Pay transaction %1: %2', $transactionId, $apiException->getUserMessage())
                );
            } else {
                $this->_logger->critical($apiException);
                throw $apiException;
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new LocalizedException(
                __('Unable to VOID Sage Pay transaction %1: %2', $transactionId, $e->getMessage())
            );
        }

        return $this;
    }

    /**
     * Voids transaction on cancel action
     *
     * @param InfoInterface $payment
     * @return $this
     * @throws LocalizedException
     */
    public function cancel(InfoInterface $payment)
    {
        if ($this->canVoid()) {
            $this->void($payment);
        }
        return $this;
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param DataObject $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject) // @codingStandardsIgnoreLine
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        //disable sales email
        $order->setCanSendNewEmailFlag(false);

        //set pending payment state
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');

        //notified state
        $stateObject->setIsNotified(false);
    }

    /**
     * Return magento payment action
     *
     * @return mixed
     */
    public function getConfigPaymentAction()
    {
        return $this->config->getPaymentAction();
    }

    /**
     * Validate CC type and country allowed
     *
     * @return $this
     * @throws LocalizedException
     */
    public function validate()
    {
        $info = $this->getInfoInstance();
        $errorMsg = false;

        //validate country
        if ($this->config->getAreSpecificCountriesAllowed() == 1) {
            $availableCountries = explode(',', $this->config->getSpecificCountries());
            if (!in_array($info->getOrder()->getBillingAddress()->getCountryId(), $availableCountries)) {
                $errorMsg = __('You can\'t use the payment type you selected to make payments to the billing country.');
            }
        }

        //check allowed card types
        if ($this->config->dropInEnabled() === false) {
            $availableTypes = explode(',', $this->config->setMethodCode(Config::METHOD_PI)->getAllowedCcTypes());
            if (!in_array($info->getCcType(), $availableTypes)) {
                $errorMsg = __('This credit card type is not allowed for this payment method');
            }
        }

        if ($errorMsg) {
            throw new LocalizedException($errorMsg);
        }

        return $this;
    }

    /**
     * Using internal pages for input payment data
     * Can be used in admin
     *
     * @return bool
     */
    public function canUseInternal()
    {
        $configEnabled = (bool)(int)$this->config->setMethodCode(Config::METHOD_PI)->isMethodActiveMoto();

        return $this->_canUseInternal && $configEnabled;
    }

    /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        $areaCode = $this->_context->getAppState()->getAreaCode();

        $moto = '';
        if ($areaCode == 'adminhtml') {
            $moto .= '_moto';
        }

        return (bool)(int)$this->getConfigData('active' . $moto, $storeId);
    }

    /**
     * @param $apiException
     * @return bool
     */
    private function exceptionCodeIsInvalidTransactionState($apiException)
    {
        return $apiException->getCode() == ApiException::INVALID_TRANSACTION_STATE;
    }
}
