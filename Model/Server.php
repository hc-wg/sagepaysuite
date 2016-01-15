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
use Magento\Framework\Exception\LocalizedException;

/**
 * SagePaySuite SERVER Module
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Server extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * @var string
     */
    protected $_code = \Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER;

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
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\Transaction
     */
    protected $_transactionsApi;


    protected $_isInitializeNeeded = true;


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
        \Ebizmarts\SagePaySuite\Model\Api\Transaction $transactionsApi,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
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
            $resource,
            $resourceCollection,
            $data
        );

        $this->_suiteHelper = $suiteHelper;
        $this->_transactionFactory = $transactionFactory;
        //$this->_messageManager = $context->getMessageManager();
        $this->_transactionsApi = $transactionsApi;
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
    }


    /**
     * Check whether payment method can be used
     * @param Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote);
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
        try {
            $action = "with";
            $order = $payment->getOrder();

            if ($payment->getLastTransId() && $order->getState() != \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {

                $transactionId = $payment->getLastTransId();

                if ($this->_config->getSagepayPaymentAction() == \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER) {
                    $action = 'releasing';
                    $result = $this->_transactionsApi->releaseTransaction($transactionId, $amount);
                } elseif ($this->_config->getSagepayPaymentAction() == \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE) {
                    $action = 'authorizing';
                    $result = $this->_transactionsApi->authorizeTransaction($transactionId, $amount, $order->getIncrementId());
                }

                $payment->setIsTransactionClosed(1);
            }

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            throw new LocalizedException(__('There was an error ' . $action . ' Sage Pay transaction ' . $transactionId . ": " . $apiException->getUserMessage()));

        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new LocalizedException(__('There was an error ' . $action . ' Sage Pay transaction ' . $transactionId . ": " . $e->getMessage()));
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

            $result = $this->_transactionsApi->refundTransaction($transactionId, $amount, $order->getIncrementId());
            $result = $result["data"];

            $payment->setIsTransactionClosed(1)
                ->setShouldCloseParentTransaction(1);


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
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        //set pending payment state
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
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
     * @param $quote
     * return array
     */
    public function generateRequest($quote,$customer_data,$assignedVendorTxCode,$notificationUrl,$saveToken = false,$token=null){

        $billing_address = $quote->getBillingAddress();
        $shipping_address = $quote->getShippingAddress();

        $data = array();
        $data["VPSProtocol"] = $this->_config->getVPSProtocol();
        $data["TxType"] = $this->_config->getSagepayPaymentAction();
        $data["Vendor"] = $this->_config->getVendorname();
        $data["VendorTxCode"] = $assignedVendorTxCode;
        $data["Amount"] = number_format($quote->getGrandTotal(), 2, '.', '');
        $data["Currency"] = $quote->getQuoteCurrencyCode();
        $data["Description"] = "Magento transaction";
        $data["NotificationURL"] = $notificationUrl;
        $data["BillingSurname"] = substr($billing_address->getLastname(), 0, 20);
        $data["BillingFirstnames"] = substr($billing_address->getFirstname(), 0, 20);
        $data["BillingAddress1"] = substr($billing_address->getStreetLine(1), 0, 100);
        $data["BillingCity"] = substr($billing_address->getCity(), 0,  40);
        $data["BillingState"] = substr($billing_address->getRegionCode(), 0, 2);
        $data["BillingPostCode"] = substr($billing_address->getPostcode(), 0, 10);
        $data["BillingCountry"] = substr($billing_address->getCountryId(), 0, 2);
        $data["DeliverySurname"] = substr($shipping_address->getLastname(), 0, 20);
        $data["DeliveryFirstnames"] = substr($shipping_address->getFirstname(), 0, 20);
        $data["DeliveryAddress1"] = substr($shipping_address->getStreetLine(1), 0, 100);
        $data["DeliveryCity"] = substr($shipping_address->getCity(), 0,  40);
        $data["DeliveryState"] = substr($shipping_address->getRegionCode(), 0, 2);
        $data["DeliveryPostCode"] = substr($shipping_address->getPostcode(), 0, 10);
        $data["DeliveryCountry"] = substr($shipping_address->getCountryId(), 0, 2);

        //token
        if($saveToken == true){
            $data["CreateToken"] = 1;
        }
        if(!is_null($token)){
            $data["StoreToken"] = 1;
            $data["Token"] = $token;
        }

        //not mandatory
//        BillingAddress2
//        BillingPhone
//        DeliveryAddress2
//        DeliveryPhone
//        CustomerEMail
//        Basket
//        AllowGiftAid
//        ApplyAVSCV2
//        Apply3DSecure
//        Profile
//        BillingAgreement
//        AccountType
//        BasketXML
//        CustomerXML
//        SurchargeXML
//        VendorData
//        ReferrerID
//        Language
//        Website
//        FIRecipientAcctNumber
//        FIRecipientSurname
//        FIRecipientPostcode
//        FIRecipientDoB

        return $data;
    }
}
