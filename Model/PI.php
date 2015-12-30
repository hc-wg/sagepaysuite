<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use Magento\Payment\Model\InfoInterface;
use Ebizmarts\SagePaySuite\Model\Api\PIRestApi;
use Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;

/**
 * Class PI
 * @package Ebizmarts\SagePaySuite\Model
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PI extends \Magento\Payment\Model\Method\Cc
{

    /**
     * @var string
     */
    protected $_code = \Ebizmarts\SagePaySuite\Model\Config::METHOD_PI;

    /**
     * @var string
     */
    protected $_infoBlockType = 'Ebizmarts\SagePaySuite\Block\Info';

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * @var bool
     */
    protected $_canSaveCc = false;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $config;

    /**
     * @var TransactionCollectionFactory
     */
    protected $salesTransactionCollectionFactory;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetaData;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRestApi
     */
    protected $_pirestapi;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Transaction
     */
    protected $_transactionsApi;

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

    protected $_isInitializeNeeded = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canReviewPayment = true;

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
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        PIRestApi $pirestapi,
        \Ebizmarts\SagePaySuite\Model\Api\Transaction $transactionsApi,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Magento\Framework\App\RequestInterface $request,
        TransactionCollectionFactory $salesTransactionCollectionFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
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
        $this->config = $config;
        $this->config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->salesTransactionCollectionFactory = $salesTransactionCollectionFactory;
        $this->productMetaData = $productMetaData;
        $this->regionFactory = $regionFactory;
        $this->_pirestapi = $pirestapi;
        $this->_transactionsApi = $transactionsApi;
        $this->_suiteHelper = $suiteHelper;
        $this->_transactionFactory = $transactionFactory;
        //$this->_messageManager = $context->getMessageManager();
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $infoInstance = $this->getInfoInstance();
        $infoInstance->setAdditionalInformation('cc_last4', $data->getData('cc_last4'));
        $infoInstance->setAdditionalInformation('merchant_session_Key', $data->getData('merchant_session_Key'));
        $infoInstance->setAdditionalInformation('card_identifier', $data->getData('card_identifier'));
        return $this;
    }

//    /**
//     * Validate data
//     *
//     * @return $this
//     * @throws \Magento\Framework\Exception\LocalizedException
//     */
//    public function validate()
//    {
//        $info = $this->getInfoInstance();
//        if ($info instanceof \Magento\Sales\Model\Order\Payment) {
//            $billingCountry = $info->getOrder()->getBillingAddress()->getCountryId();
//        } else {
//            $billingCountry = $info->getQuote()->getBillingAddress()->getCountryId();
//        }
//
//        if (!$this->config->canUseForCountry($billingCountry)) {
//            throw new LocalizedException(__('Selected payment type is not allowed for billing country.'));
//        }
//
//        return $this;
//    }

    /**
     * Authorizes specified amount
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function authorize(InfoInterface $payment, $amount)
    {

    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    protected function verify3dSecure()
    {
//        return $this->config->is3dSecureEnabled() &&
//        $this->_appState->getAreaCode() !== \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    protected function partialCapture($payment, $amount)
    {

    }

    /**
     * Captures specified amount
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {


        try {



        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            throw $apiException;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new \Magento\Framework\Validator\Exception(__('Unable to capture payment.'));
        }

        return $this;
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

            $transactionId = $payment->getLastTransId();
            $order = $payment->getOrder();

            $result = $this->_transactionsApi->refundTransaction($transactionId, $amount, $order->getIncrementId());
            $result = $result["data"];

            //create refund transaction
            $refundTransaction = $this->_transactionFactory->create()
                ->setOrderPaymentObject($payment)
                ->setTxnId($result["VPSTxId"])
                ->setParentTxnId($transactionId)
                ->setTxnType(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
                ->setPaymentId($payment->getId());

            $refundTransaction->save();
            $refundTransaction->setIsClosed(true);

            //$this->_messageManager->addSuccess(__("Sage Pay transaction " . $transactionId . " successfully refunded."));

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            throw new LocalizedException(__('There was an error refunding Sage Pay transaction ' . $transactionId . ": " . $apiException->getUserMessage()));

        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new LocalizedException(__('There was an error refunding Sage Pay transaction ' . $transactionId));
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
        $transaction_id = $payment->getLastTransId();

        try {

            $result = $this->_transactionsApi->voidTransaction($transaction_id);
            $result = $result["data"];

            //create void transaction
            //not for now
//            $refundTransaction = $this->_transactionFactory->create()
//                ->setOrderPaymentObject($payment)
//                ->setTxnId($result["VPSTxId"])
//                ->setParentTxnId($transaction_id)
//                ->setTxnType(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
//                ->setPaymentId($payment->getId());
//
//            $refundTransaction->save();
//            $refundTransaction->setIsClosed(true);

            //$this->_messageManager->addSuccess(__("Sage Pay transaction " . $transaction_id . " successfully voided."));


        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {

            if ($apiException->getCode() == \Ebizmarts\SagePaySuite\Model\Api\ApiException::INVALID_TRANSACTION_STATE) {
                //unable to void transaction
                throw new LocalizedException(__('Unable to VOID Sage Pay transaction ' . $transaction_id . ', you will need to refund it instead.'));
            } else {
                $this->_logger->critical($apiException);
                throw $apiException;
            }

        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new LocalizedException(__('There was an error voiding transaction ' . $transaction_id));
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
        $this->void($payment);
        return $this;
    }

    /**
     * Check whether payment method is applicable to quote
     * Purposed to allow use in controllers some logic that was implemented in blocks only before
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
//        $country = null;
//        if($quote != null && $quote->getBillingAddress() != null){
//            $country = $quote->getBillingAddress()->getCountryId();
//        }
//
//        return $this->config->isMethodAvailable($this->_code,$country);
        return parent::isAvailable($quote);
    }

    /**
     * @return bool
     */
    public function canVoid()
    {
        return $this->_canVoid;
    }

    /**
     * Validate data
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate()
    {
        $info = $this->getInfoInstance();
        if ($info instanceof \Magento\Sales\Model\Order\Payment) {
            $billingCountry = $info->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $info->getQuote()->getBillingAddress()->getCountryId();
        }
        if (!$this->config->canUseForCountry($billingCountry)) {
            throw new LocalizedException(__('Selected payment type is not allowed for billing country.'));
        }
        return $this;
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
        //disable sales email
        $payment = $this->getInfoInstance();

//        if($payment->getAdditionalInformation('statusCode') == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS){
//
//        }

        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        //set pending payment state
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    /**
     * Attempt to accept a pending payment
     *
     * @param \Magento\Payment\Model\Info|Payment $payment
     * @return bool
     */
    public function acceptPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::acceptPayment($payment);
    }
}