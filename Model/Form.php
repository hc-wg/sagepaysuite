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
 * SagePaySuite FORM Module
 */
class Form extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = \Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM; // @codingStandardsIgnoreLine

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
    protected $_canUseCheckout = true; // @codingStandardsIgnoreLine

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

    private $_context;

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

        $this->_context     = $context;
        $this->_suiteHelper = $suiteHelper;
        $this->_sharedApi   = $sharedApi;
        $this->_config      = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
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

        if ($payment->getLastTransId()) {
            try {
                $transactionId = $payment->getLastTransId();

                $paymentAction = $this->_config->getSagepayPaymentAction();
                if ($payment->getAdditionalInformation('paymentAction')) {
                    $paymentAction = $payment->getAdditionalInformation('paymentAction');
                }

                if ($paymentAction == \Ebizmarts\SagePaySuite\Model\Config::ACTION_DEFER) {
                    $action = 'releasing';
                    $this->_sharedApi->releaseTransaction($transactionId, $amount);
                } elseif ($paymentAction == \Ebizmarts\SagePaySuite\Model\Config::ACTION_AUTHENTICATE) {
                    $action = 'authorizing';
                    $this->_sharedApi->authorizeTransaction(
                        $transactionId,
                        $amount,
                        $payment->getOrder()->getIncrementId()
                    );
                }

                $payment->setIsTransactionClosed(1);
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
                    __(
                        "There was an error %1 Sage Pay transaction %2: %3",
                        $action,
                        $transactionId,
                        $e->getUserMessage()
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
     * Return magento payment action
     *
     * @return mixed
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
            $cryptPass  = $this->_config->getFormEncryptedPassword();
            $strDecoded = $this->getDecryptedRequest($cryptPass, $crypt);

            $responseRaw = explode('&', $strDecoded);
            $response = [];

            $responseRawCnt = count($responseRaw);
            for ($i = 0; $i < $responseRawCnt; $i++) {
                $strField = explode('=', $responseRaw[$i]);
                $response[$strField[0]] = $strField[1];
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
        //** remove the first char which is @ to flag this is AES encrypted
        $hex = substr($string, 1);

        // Throw exception if string is malformed
        if (!preg_match('/^[0-9a-fA-F]+$/', $hex)) {
            throw new LocalizedException(__('Invalid encryption string'));
        }

        //** HEX decoding
        $strIn = pack('H*', $hex);

        $decryptor = $this->getObjManager()
            ->create('\phpseclib\Crypt\AES', ['mode' => \phpseclib\Crypt\Base::MODE_CBC]);
        $decryptor->setBlockLength(128);
        $decryptor->setKey($password);
        $decryptor->setIV($password);

        return $decryptor->decrypt($strIn);
    }

    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    private function getObjManager()
    {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Using internal pages for input payment data
     * Can be used in admin
     *
     * @return bool
     */
    public function canUseInternal()
    {
        $configEnabled = (bool)(int)$this->_config->setMethodCode(
            \Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM
        )->isMethodActiveMoto();
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
}
