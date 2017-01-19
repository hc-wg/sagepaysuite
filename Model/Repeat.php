<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * SagePaySuite REPEAT Module
 */
class Repeat extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = \Ebizmarts\SagePaySuite\Model\Config::METHOD_REPEAT; // @codingStandardsIgnoreLine

    /**
     * @var string
     */
    protected $_infoBlockType = 'Ebizmarts\SagePaySuite\Block\Info'; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canOrder = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canAuthorize = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapturePartial = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefund = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canVoid = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = false; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = true; // @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canReviewPayment = true; // @codingStandardsIgnoreLine

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true; // @codingStandardsIgnoreLine

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared
     */
    private $_sharedApi;

    /**
     * @var Logger
     */
    private $_suiteLogger;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param Api\Shared $sharedApi
     * @param \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper
     * @param Config $config
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
        \Ebizmarts\SagePaySuite\Model\Api\Shared $sharedApi,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Config $config,
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
            $resource,
            $resourceCollection,
            $data
        );

        $this->_suiteHelper = $suiteHelper;
        $this->_sharedApi   = $sharedApi;
        $this->_config      = $config;
        $this->_suiteLogger = $suiteLogger;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_REPEAT);
    }

    /**
     * Capture payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return $this
     * @throws LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $action = "with";
        $transactionId = "";

        if ($payment->getLastTransId()) {
            try {
                $transactionId = $payment->getLastTransId();

                $paymentAction = $this->_config->getSagepayPaymentAction();
                if ($payment->getAdditionalInformation('paymentAction')) {
                    $paymentAction = $payment->getAdditionalInformation('paymentAction');
                }

                if ($paymentAction == \Ebizmarts\SagePaySuite\Model\Config::ACTION_REPEAT_DEFERRED) {
                    $action = 'releasing';
                    $this->_sharedApi->releaseTransaction($transactionId, $amount);
                }

                $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, "CAPTURE", [__METHOD__, __LINE__]);

                $payment->setIsTransactionClosed(1);
            } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
                $this->_suiteLogger->logException($apiException, [__METHOD__, __LINE__]);
                throw new LocalizedException(
                    __(
                        'There was an error %1 Sage Pay transaction %2: %3',
                        $action,
                        $transactionId,
                        $apiException->getUserMessage()
                    )
                );
            } catch (\Exception $e) {
                $this->_suiteLogger->logException($e, [__METHOD__, __LINE__]);
                throw new LocalizedException(
                    __(
                        'There was an error %1 Sage Pay transaction %2: %3',
                        $action,
                        $transactionId,
                        $e->getMessage()
                    )
                );
            }
        }
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
        try {
            $transactionId = $this->_suiteHelper->clearTransactionId($payment->getLastTransId());
            $order = $payment->getOrder();

            $this->_sharedApi->refundTransaction($transactionId, $amount, $order->getIncrementId());

            $payment->setIsTransactionClosed(1);
            $payment->setShouldCloseParentTransaction(1);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_suiteLogger->logException($apiException, [__METHOD__, __LINE__]);
            throw new LocalizedException(
                __(
                    'There was an error refunding Sage Pay transaction %1: %2',
                    $transactionId,
                    $apiException->getUserMessage()
                )
            );
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e, [__METHOD__, __LINE__]);
            throw new LocalizedException(
                __('There was an error refunding Sage Pay transaction ' . $transactionId . ": " . $e->getMessage())
            );
        }

        return $this;
    }

    /**
     * Return magento payment action
     *
     * @return mixed
     */
    public function getConfigPaymentAction()
    {
        return $this->_config->getPaymentAction();
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payAction = $paymentAction;
        $payment   = $this->getInfoInstance();
        $order     = $payment->getOrder();

        //disable sales email
        $order->setCanSendNewEmailFlag(false);

        //set pending payment state
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');

        //notified state
        $stateObject->setIsNotified(false);
    }

    /**
     * Set initialized flag to capture payment
     */
    public function markAsInitialized()
    {
        $this->_isInitializeNeeded = false;
    }
}
