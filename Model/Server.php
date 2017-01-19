<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Model;

use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Sage Pay Suite SERVER model
 */
class Server extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = \Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER;// @codingStandardsIgnoreLine

    /**
     * @var string
     */
    protected $_infoBlockType = 'Ebizmarts\SagePaySuite\Block\Info';// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canOrder = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canAuthorize = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapturePartial = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefund = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canVoid = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = false;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;// @codingStandardsIgnoreLine

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canReviewPayment = true;// @codingStandardsIgnoreLine

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    private $_transactionFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Shared
     */
    private $_sharedApi;

    protected $_isInitializeNeeded = true;// @codingStandardsIgnoreLine


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
        \Ebizmarts\SagePaySuite\Model\Api\Shared $sharedApi,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
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

        $this->_suiteHelper        = $suiteHelper;
        $this->_transactionFactory = $transactionFactory;
        $this->_sharedApi          = $sharedApi;
        $this->_config             = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
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
        try {
            $action = "with";
            $order = $payment->getOrder();

            if ($payment->getLastTransId() && $order->getState() != \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
                $transactionId = $payment->getLastTransId();
                $paymentAction = $payment->getAdditionalInformation('paymentAction') ?
                    $payment->getAdditionalInformation('paymentAction') : $this->_config->getSagepayPaymentAction();

                if ($paymentAction == \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER) {
                    $action = 'releasing';
                    $this->_sharedApi->releaseTransaction($transactionId, $amount);
                } elseif ($paymentAction == \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE) {
                    $action = 'authorizing';
                    $this->_sharedApi->authorizeTransaction($transactionId, $amount, $order->getIncrementId());
                }

                $payment->setIsTransactionClosed(1);
            }
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            throw new LocalizedException(
                __(
                    "There was an error %1 Sage Pay transaction %2: %3",
                    $action,
                    $transactionId,
                    $apiException->getUserMessage()
                )
            );
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new LocalizedException(
                __("There was an error %1 Sage Pay transaction %2: %3", $action, $transactionId, $e->getMessage())
            );
        }

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

            $result = $this->_sharedApi->refundTransaction($transactionId, $amount, $order->getIncrementId());
            $result = $result["data"];

            $payment->setIsTransactionClosed(1);
            $payment->setShouldCloseParentTransaction(1);
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            throw new LocalizedException(
                __(
                    "There was an error refunding Sage Pay transaction %1: %2",
                    $transactionId,
                    $apiException->getUserMessage()
                )
            );
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new LocalizedException(
                __(
                    "There was an error refunding Sage Pay transaction %1: %2",
                    $transactionId,
                    $e->getMessage()
                )
            );
        }

        return $this;
    }

    /**
     * Check void availability
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @internal param \Magento\Framework\Object $payment
     */
    public function canVoid()
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
            return false;
        }

        return $this->_canVoid;
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     * @return void
     */
    // @codingStandardsIgnoreStart
    public function initialize($paymentAction, $stateObject)
    {
        //disable sales email
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        //set pending payment state
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }
    // @codingStandardsIgnoreEnd

    /**
     * Return magento payment action
     *
     * @return mixed
     */
    public function getConfigPaymentAction()
    {
        return $this->_config->getPaymentAction();
    }
}
