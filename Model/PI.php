<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use Magento\Payment\Model\InfoInterface;
use Ebizmarts\SagePaySuite\Model\Config;
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
    protected $_canUseInternal = true;

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

        //$ccType = $info->getCcType();
//        if (!$ccType) {
//            $token = $this->getInfoInstance()->getAdditionalInformation('cc_token');
//            if ($token) {
//                $ccType = $this->vault->getSavedCardType($token);
//            }
//        }

//        if ($ccType) {
//            $error = $this->config->canUseCcTypeForCountry($billingCountry, $ccType);
//            if ($error) {
//                throw new LocalizedException($error);
//            }
//        }

        return $this;
    }

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
        //return $this->braintreeAuthorize($payment, $amount, false);
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

        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        $vendorTxCode = $this->_suiteHelper->generateVendorTxCode($order->getIncrementId());

        try {
            $data = [
                'transactionType' => "Payment", //only supported method for now
                'paymentMethod' => [
                    'card' => [
                        'merchantSessionKey' => $payment->getAdditionalInformation("merchant_session_Key"),
                        'cardIdentifier' => $payment->getAdditionalInformation("card_identifier")
                    ]
                ],
                'vendorTxCode' => $vendorTxCode,
                'amount' => $amount * 100,
                'currency' => $order->getBaseCurrencyCode(),
                'description' => "Demo transaction",
                'customerFirstName' => $billing->getFirstname(),
                'customerLastName' => $billing->getLastname(),
                'billingAddress' => [
                    'address1' => $billing->getStreetLine(1),
                    'city' => $billing->getCity(),
                    'postalCode' => $billing->getPostCode(),
                    'country' => $billing->getCountryId()
                ],
                'entryMethod' => "Ecommerce",
                'apply3DSecure' => "Disable"
            ];

            if ($billing->getCountryId() == "US") {
                $state = $billing->getRegionCode();
                if (strlen($state) > 2) {
                    $state = "CA"; //hardcoded as the code is not working correctly
                }
                $data["billingAddress"]["state"] = $state;
            }

            $capture_result = $this->_pirestapi->capture($data);

            if ($capture_result->statusCode == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {

                $payment->setTransactionId($capture_result->transactionId);
                $payment->setIsTransactionClosed(1);
                $payment->setAdditionalInformation('statusCode', $capture_result->statusCode);
                $payment->setAdditionalInformation('transactionType', $capture_result->transactionType);
                $payment->setAdditionalInformation('statusDetail', $capture_result->statusDetail);
                $payment->setAdditionalInformation('vendorTxCode', $vendorTxCode);
                $payment->setCcLast4($payment->getAdditionalInformation("cc_last4"));

            } elseif ($capture_result->statusCode == \Ebizmarts\SagePaySuite\Model\Config::AUTH3D_REQUIRED_STATUS) {

                //3D required
                //@toDo

            } else {
                throw new \Magento\Framework\Validator\Exception(__('Invalid Sage Pay status.'));
            }

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
//        if (parent::isAvailable($quote)) {
//            if ($quote != null) {
//                $availableCcTypes = $this->config->getApplicableCardTypes($quote->getBillingAddress()->getCountryId());
//                if (!$availableCcTypes) {
//                    return false;
//                }
//            }
//        } else {
//            return false;
//        }
        return true;
    }

    /**
     * @return bool
     */
    public function canVoid()
    {
//        if (($order = $this->_registry->registry('current_order'))
//            && $order->getId() && $order->hasInvoices()
//        ) {
//            return false;
//        }
        return $this->_canVoid;
    }

}