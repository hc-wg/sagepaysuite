<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Resource\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use Magento\Payment\Model\InfoInterface;
use Ebizmarts\SagePaySuite\Model\Api\PIRest;

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
    protected $_code                    = \Ebizmarts\SagePaySuite\Model\Config::METHOD_PI;

    /**
     * @var bool
     */
    protected $_isGateway               = true;

    /**
     * @var bool
     */
    protected $_canAuthorize            = true;

    /**
     * @var bool
     */
    protected $_canCapture              = true;

    /**
     * @var bool
     */
    protected $_canCapturePartial       = true;

    /**
     * @var bool
     */
    protected $_canRefund               = true;

    /**
     * @var bool
     */
    protected $_canVoid                 = true;

    /**
     * @var bool
     */
    protected $_canUseInternal          = true;

    /**
     * @var bool
     */
    protected $_canUseCheckout          = true;

    /**
     * @var bool
     */
    protected $_canSaveCc               = false;

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

    protected $_pirestapi;

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
     * @param TransactionCollectionFactory $salesTransactionCollectionFactory
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetaData
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Framework\Model\Resource\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
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
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        TransactionCollectionFactory $salesTransactionCollectionFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirestapi,
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
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
        $this->config = $config;
        $this->salesTransactionCollectionFactory = $salesTransactionCollectionFactory;
        $this->productMetaData = $productMetaData;
        $this->regionFactory = $regionFactory;
        $this->_pirestapi = $pirestapi;
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

        $ccType = $info->getCcType();
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
     * @param string|null $token
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
//    protected function populateAuthorizeRequest(InfoInterface $payment, $token)
//    {
//        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
//        $order = $payment->getOrder();
//        $orderId = $order->getIncrementId();
//        $billing = $order->getBillingAddress();
//        $shipping = $order->getShippingAddress();
//        $transactionParams = [
//            'channel'   => $this->getChannel(),
//            'orderId'   => $orderId,
//            'customer'  => [
//                'firstName' => $billing->getFirstname(),
//                'lastName'  => $billing->getLastname(),
//                'company'   => $billing->getCompany(),
//                'phone'     => $billing->getTelephone(),
//                'fax'       => $billing->getFax(),
//                'email'     => $order->getCustomerEmail(),
//            ]
//        ];
//        $customerId = $this->braintreeHelper
//            ->generateCustomerId($order->getCustomerId(), $order->getCustomerEmail());
//
//        $merchantAccountId = $this->config->getMerchantAccountId();
//        if ($merchantAccountId) {
//            $transactionParams['merchantAccountId'] = $merchantAccountId;
//        }
//
//        if (!$this->isTokenAllowed()) {
//            $token = null;
//        } elseif (!$token) {
//            $token = $this->getInfoInstance()->getAdditionalInformation('cc_token');
//        }
//
//        if ($token) {
//            $transactionParams['paymentMethodToken'] = $token;
//            $transactionParams['customerId'] = $customerId;
//        } elseif ($this->getInfoInstance()->getAdditionalInformation('payment_method_nonce')) {
//            $transactionParams['paymentMethodNonce'] =
//                $this->getInfoInstance()->getAdditionalInformation('payment_method_nonce');
//            if ($this->isPaymentMethodNonceForCc()) {
//                if ($order->getCustomerId() && $this->config->useVault()) {
//                    if ($this->getInfoInstance()->getAdditionalInformation('store_in_vault')) {
//                        $last4 = $this->getInfoInstance()->getAdditionalInformation('cc_last4');
//                        if ($this->shouldSaveCard($last4)) {
//                            $transactionParams['options']['storeInVaultOnSuccess'] = true;
//                        }
//                    } else {
//                        $transactionParams['options']['storeInVault'] = false;
//                    }
//                    if ($this->vault->exists($customerId)) {
//                        $transactionParams['customerId'] = $customerId;
//                        //TODO: How can we update customer information?
//                        unset($transactionParams['customer']);
//                    } else {
//                        $transactionParams['customer']['id'] = $customerId;
//                    }
//                }
//
//                $transactionParams['creditCard'] = [
//                    'cardholderName'    => $billing->getFirstname() . ' ' . $billing->getLastname(),
//                ];
//            }
//            $transactionParams['billing']  = $this->toBraintreeAddress($billing);
//            $transactionParams['shipping'] = $this->toBraintreeAddress($shipping);
//            $transactionParams['options']['addBillingAddressToPaymentMethod']  = true;
//        } else {
//            throw new LocalizedException(__('Incomplete payment information.'));
//        }
//
//        if ($this->verify3dSecure()) {
//            $transactionParams['options']['three_d_secure'] = [
//                'required' => true,
//            ];
//
//            if ($token && $this->getInfoInstance()->getAdditionalInformation('payment_method_nonce')) {
//                $transactionParams['paymentMethodNonce'] =
//                    $this->getInfoInstance()->getAdditionalInformation('payment_method_nonce');
//                unset($transactionParams['paymentMethodToken']);
//            }
//        }
//
//        if ($this->config->isFraudProtectionEnabled() &&
//            strlen($this->getInfoInstance()->getAdditionalInformation('device_data')) > 0) {
//            $transactionParams['deviceData'] = $this->getInfoInstance()->getAdditionalInformation('device_data');
//        }
//        return $transactionParams;
//    }


    /**
     * Returns extra transaction information, to be logged as part of the order payment
     *
     * @param \Braintree_Transaction $transaction
     * @return array
     */
//    protected function getExtraTransactionInformation(\Braintree_Transaction $transaction)
//    {
//        $data = [];
//        $loggedFields =[
//            'avsErrorResponseCode',
//            'avsPostalCodeResponseCode',
//            'avsStreetAddressResponseCode',
//            'cvvResponseCode',
//            'gatewayRejectionReason',
//            'processorAuthorizationCode',
//            'processorResponseCode',
//            'processorResponseText',
//        ];
//        foreach ($loggedFields as $loggedField) {
//            if (!empty($transaction->{$loggedField})) {
//                $data[$loggedField] = $transaction->{$loggedField};
//            }
//        }
//        return $data;
//    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    protected function partialCapture($payment, $amount)
    {
//        $collection = $this->salesTransactionCollectionFactory->create()
//            ->addPaymentIdFilter($payment->getId())
//            ->addTxnTypeFilter(PaymentTransaction::TYPE_AUTH)
//            ->setOrder('created_at', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
//            ->setOrder('transaction_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
//            ->setPageSize(1)
//            ->setCurPage(1);
//        $authTransaction = $collection->getFirstItem();
//        if (!$authTransaction->getId()) {
//            throw new LocalizedException(__('Can not find original authorization transaction for partial capture'));
//        }
//        if (($token = $authTransaction->getAdditionalInformation('token'))) {
//            //order was placed using saved card or card was saved during checkout token
//            $found = true;
//            try {
//                $this->braintreeCreditCard->find($token);
//            } catch (\Exception $e) {
//                $found = false;
//            }
//            if ($found) {
//                $this->config->initEnvironment($payment->getOrder()->getStoreId());
//                $this->braintreeAuthorize($payment, $amount, true, $token);
//            } else {
//                // case if payment token is no more applicable. attempt to clone transaction
//                $result = $this->cloneTransaction($amount, $authTransaction->getTxnId());
//                if ($result && $result->success) {
//                    $this->processSuccessResult($payment, $result, $amount);
//                } else {
//                    throw new LocalizedException($this->errorHelper->parseBraintreeError($result));
//                }
//            }
//        } else {
//            // order was placed without saved card and card wasn't saved during checkout
//            $result = $this->cloneTransaction($amount, $authTransaction->getTxnId());
//            if ($result->success) {
//                $this->processSuccessResult($payment, $result, $amount);
//            } else {
//                throw new LocalizedException($this->errorHelper->parseBraintreeError($result));
//            }
//        }
//        return $this;
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
        if($payment->getLastTransId()) {
            //return $this->captureAuthorized($payment,$amount);
            //@toDo
            return null;
        }

        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        try {
            $data = [
                'transactionType' => "Payment", //only supported method for now
                'paymentMethod' => [
                    'card' => [
                        'merchantSessionKey' => $payment->getAdditionalInformation("merchant_session_Key"),
                        'cardIdentifier' => $payment->getAdditionalInformation("card_identifier")
                    ]
                ],
                'vendorTxCode' => substr($order->getId() . date('Y-m-d-H-i-s-') . time(), 0, 40),
                'amount' => $amount,
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
                'entryMethod' =>"Ecommerce"
            ];

            $transaction = $this->_pirestapi->capture($data);

            $payment->setTransactionId($transaction->id);
            $payment->setIsTransactionClosed(1);
//            $payment->setAdditionalInformation('cvc_check',$charge['source']['cvc_check']);
//            $payment->setAdditionalInformation('address_line1_check',$charge['source']['address_line1_check']);
//            $payment->setAdditionalInformation('address_zip_check',$charge['source']['address_zip_check']);
        } catch(\Exception $e) {
            throw new \Magento\Framework\Validator\Exception(__('Payment capture error.'));
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
//        $transactionId = $this->braintreeHelper->clearTransactionId($payment->getRefundTransactionId());
//        try {
//            $transaction = $this->braintreeTransaction->find($transactionId);
//            $this->_debug($payment->getCcTransId());
//            $this->_debug($transaction);
//            if ($transaction->status === \Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT) {
//                if ($transaction->amount != $amount) {
//                    $message = __('This refund is for a partial amount but the Transaction has not settled.')
//                        ->getText();
//                    $message .= ' ';
//                    $message .= __('Please wait 24 hours before trying to issue a partial refund.')
//                        ->getText();
//                    throw new LocalizedException(
//                        __($message)
//                    );
//                }
//            }
//
//            $canVoid = ($transaction->status === \Braintree_Transaction::AUTHORIZED
//                || $transaction->status === \Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT);
//            $result = $canVoid
//                ? $this->braintreeTransaction->void($transactionId)
//                : $this->braintreeTransaction->refund($transactionId, $amount);
//            $this->_debug($result);
//            if ($result->success) {
//                $payment->setIsTransactionClosed(1);
//            } else {
//                throw new LocalizedException($this->errorHelper->parseBraintreeError($result));
//            }
//        } catch (\Exception $e) {
//            $message = $e->getMessage();
//            throw new LocalizedException(__('There was an error refunding the transaction: %1.', $message));
//        }
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
//        $transactionIds = $this->getTransactionsToVoid($payment);
//        $message = false;
//        foreach ($transactionIds as $transactionId) {
//            $transaction = $this->braintreeTransaction->find($transactionId);
//            if ($transaction->status !== \Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT &&
//                $transaction->status !== \Braintree_Transaction::AUTHORIZED) {
//                throw new LocalizedException(
//                    __('Some transactions are already settled or voided and cannot be voided.')
//                );
//            }
//            if ($transaction->status === \Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT) {
//                $message = __('Voided capture.') ;
//            }
//        }
//        $errors = '';
//        foreach ($transactionIds as $transactionId) {
//            $this->_debug('void-' . $transactionId);
//            $result = $this->braintreeTransaction->void($transactionId);
//            $this->_debug($result);
//            if (!$result->success) {
//                $errors .= ' ' . $this->errorHelper->parseBraintreeError($result)->getText();
//            } elseif ($message) {
//                $payment->setMessage($message);
//            }
//        }
//        if ($errors) {
//            throw new LocalizedException(__('There was an error voiding the transaction: %1.', $errors));
//        } else {
//            $match = true;
//            foreach ($transactionIds as $transactionId) {
//                $collection = $this->salesTransactionCollectionFactory->create()
//                    ->addFieldToFilter('parent_txn_id', ['eq' => $transactionId])
//                    ->addFieldToFilter('txn_type', PaymentTransaction::TYPE_VOID);
//                if ($collection->getSize() < 1) {
//                    $match = false;
//                }
//            }
//            if ($match) {
//                $payment->setIsTransactionClosed(1);
//            }
//        }
//        return $this;
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
//        try {
//            $this->void($payment);
//        } catch (\Exception $e) {
//            $this->_logger->critical($e);
//            throw new LocalizedException(__('There was an error voiding the transaction: %1.', $e->getMessage()));
//        }
        return $this;
    }

    /**
     * Check whether payment method is applicable to quote
     * Purposed to allow use in controllers some logic that was implemented in blocks only before
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
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
        if (($order = $this->_registry->registry('current_order'))
            && $order->getId() && $order->hasInvoices() ) {
            return false;
        }
        return $this->_canVoid;
    }

}