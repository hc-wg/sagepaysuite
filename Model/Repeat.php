<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
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
    protected $_code = \Ebizmarts\SagePaySuite\Model\Config::METHOD_REPEAT;

    /**
     * @var string
     */
    protected $_infoBlockType = 'Ebizmarts\SagePaySuite\Block\Info';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = true;

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
    protected $_canUseInternal = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = false;

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
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    protected $_suiteHelper;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared
     */
    protected $_sharedApi;

    /**
     * @var Logger
     */
    protected $_suiteLogger;

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
        $this->_sharedApi = $sharedApi;
        $this->_config = $config;
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
                $paymentAction = $payment->getAdditionalInformation('paymentAction') ? $payment->getAdditionalInformation('paymentAction') : $this->_config->getSagepayPaymentAction();

                if ($paymentAction == \Ebizmarts\SagePaySuite\Model\Config::ACTION_REPEAT_DEFERRED) {
                    $action = 'releasing';
                    $result = $this->_sharedApi->releaseTransaction($transactionId, $amount);
                }

                $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, "CAPTURE");

                $payment->setIsTransactionClosed(1);
            } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
                $this->_suiteLogger->logException($apiException);
                throw new LocalizedException(__('There was an error ' . $action . ' Sage Pay transaction ' . $transactionId . ": " . $apiException->getUserMessage()));
            } catch (\Exception $e) {
                $this->_suiteLogger->logException($e);
                throw new LocalizedException(__('There was an error ' . $action . ' Sage Pay transaction ' . $transactionId . ": " . $e->getMessage()));
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
            $this->_suiteLogger->logException($apiException);
            throw new LocalizedException(__('There was an error refunding Sage Pay transaction ' . $transactionId . ": " . $apiException->getUserMessage()));
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e);
            throw new LocalizedException(__('There was an error refunding Sage Pay transaction ' . $transactionId . ": " . $e->getMessage()));
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
     * Set initialized flag to capture payment
     */
    public function markAsInitialized()
    {
        $this->_isInitializeNeeded = false;
    }
}
