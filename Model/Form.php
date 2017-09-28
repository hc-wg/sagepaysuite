<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Model;

use Magento\Framework\Exception\LocalizedException;

/**
 * SagePaySuite FORM Module
 */
class Form extends \Magento\Payment\Model\Method\AbstractMethod
{
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

    /** @var \Magento\Framework\Model\Context */
    private $_context;

    /** @var bool */
    private $isInitializeNeeded = true;

    /** @var \Ebizmarts\SagePaySuite\Model\Payment */
    private $paymentOps;

    private $formCrypt;

    /**
     * Form constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param FormCrypt $formCrypt
     * @param Payment $paymentOps
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
        \Ebizmarts\SagePaySuite\Model\FormCrypt $formCrypt,
        \Ebizmarts\SagePaySuite\Model\Payment $paymentOps,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Ebizmarts\SagePaySuite\Model\Api\Shared $sharedApi,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Config $config,
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

        $this->_config      = $config;
        $this->_context     = $context;
        $this->_sharedApi   = $sharedApi;
        $this->paymentOps   = $paymentOps;
        $this->_suiteHelper = $suiteHelper;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->formCrypt    = $formCrypt;
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
        $this->paymentOps->capture($payment, $amount);
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
        $this->paymentOps->refund($payment, $amount);
        return $this;
    }

    /**
     * Return magento payment action
     *
     * @return mixed
     * @code
     */
    public function getConfigPaymentAction()
    {
        return $this->_config->getPaymentAction();
    }

    /**
     * Decode response hash from Sage Pay
     *
     * @param $crypt
     * @return array
     * @throws LocalizedException
     */
    public function decodeSagePayResponse($crypt)
    {
        if (empty($crypt)) {
            throw new LocalizedException(__('Invalid response from Sage Pay'));
        } else {
            $response = [];

            $cryptPass  = $this->_config->getFormEncryptedPassword();
            $strDecoded = $this->getDecryptedRequest($cryptPass, $crypt);

            if (false !== $strDecoded) {
                $responseRaw = explode('&', $strDecoded);

                $responseRawCnt = count($responseRaw);
                for ($i = 0; $i < $responseRawCnt; $i++) {
                    $strField = explode('=', $responseRaw[$i]);
                    $response[$strField[0]] = $strField[1];
                }
            }

            return $response;
        }
    }

    /**
     * @param $password
     * @param $string
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getDecryptedRequest($password, $string)
    {
        $this->formCrypt->initInitializationVectorAndKey($password);

        return $this->formCrypt->decrypt($string);
    }

    /**
     * Using internal pages for input payment data
     * Can be used in admin
     *
     * @return bool
     */
    public function canUseInternal()
    {
        return false;
    }

    /**
     * Is active
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
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
        $order   = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $this->paymentOps->setOrderStateAndStatus($payment, $paymentAction, $stateObject);

        $stateObject->setIsNotified(false);
    }
    // @codingStandardsIgnoreEnd

    /**
     * Flag if we need to run payment initialize while order place
     *
     * @return bool
     * @api
     */
    public function isInitializeNeeded()
    {
        return $this->isInitializeNeeded;
    }

    /**
     * Set initialized flag to capture payment
     */
    public function markAsInitialized()
    {
        $this->isInitializeNeeded = false;
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
        $order   = $payment->getOrder();
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve payment method code
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCode()
    {
        return \Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM;
    }

    public function getInfoBlockType()
    {
        return 'Ebizmarts\SagePaySuite\Block\Info';
    }

    /**
     * Retrieve payment system relation flag
     *
     * @return bool
     * @api
     */
    public function isGateway()
    {
        return true;
    }

    /**
     * Check order availability
     *
     * @return bool
     * @api
     */
    public function canOrder()
    {
        return true;
    }

    /**
     * Check authorize availability
     *
     * @return bool
     * @api
     */
    public function canAuthorize()
    {
        return true;
    }

    /**
     * Check capture availability
     *
     * @return bool
     * @api
     */
    public function canCapture()
    {
        return true;
    }

    /**
     * Check partial capture availability
     *
     * @return bool
     * @api
     */
    public function canCapturePartial()
    {
        return true;
    }

    /**
     * Check refund availability
     *
     * @return bool
     * @api
     */
    public function canRefund()
    {
        return true;
    }

    /**
     * Check partial refund availability for invoice
     *
     * @return bool
     * @api
     */
    public function canRefundPartialPerInvoice()
    {
        return true;
    }

    /**
     * Can be used in regular checkout
     *
     * @return bool
     */
    public function canUseCheckout()
    {
        return true;
    }

    /**
     * Check fetch transaction info availability
     *
     * @return bool
     * @api
     */
    public function canFetchTransactionInfo()
    {
        return true;
    }

    /**
     * Whether this method can accept or deny payment
     * @return bool
     * @api
     */
    public function canReviewPayment()
    {
        return true;
    }
}
