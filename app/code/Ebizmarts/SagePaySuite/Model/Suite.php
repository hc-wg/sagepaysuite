<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Ebizmarts\SagePaySuite\Model;

class Suite
{
    /**
     * Config instance
     *
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * SagePay info object
     *
     * @var \Ebizmarts\SagePaySuite\Model\Info
     */
    protected $_infoInstance;

    /**
     * Config model type
     *
     * @var string
     */
    protected $_configType = 'Ebizmarts\SagePaySuite\Model\Config';

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config\Factory
     */
    protected $_configFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\InfoFactory
     */
    protected $_infoFactory;

    /**
     * @param \Ebizmarts\SagePaySuite\Model\Config\Factory $configFactory
     * @param \Ebizmarts\SagePaySuite\Model\Api\Type\Factory $apiFactory
     * @param \Ebizmarts\SagePaySuite\Model\InfoFactory $infoFactory
     */
    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Config\Factory $configFactory,
        \Ebizmarts\SagePaySuite\Model\InfoFactory $infoFactory
    ) {
        $this->_configFactory = $configFactory;
        $this->_infoFactory = $infoFactory;
    }

    /**
     * Payment method code setter. Also instantiates/updates config
     *
     * @param string $code
     * @param int|null $storeId
     * @return $this
     */
    public function setMethod($code, $storeId = null)
    {
        if (null === $this->_config) {
            $params = [$code];
            if (null !== $storeId) {
                $params[] = $storeId;
            }
            $this->_config = $this->_configFactory->create($this->_configType, ['params' => $params]);
            $this->_config->setMethod($code);
        } else {
            $this->_config->setMethod($code);
            if (null !== $storeId) {
                $this->_config->setStoreId($storeId);
            }
        }
        return $this;
    }

    /**
     * Config instance setter
     *
     * @param \Ebizmarts\SagePaySuite\Model\Config $instace
     * @param int|null $storeId
     * @return $this
     */
    public function setConfig(\Ebizmarts\SagePaySuite\Model\Config $instace, $storeId = null)
    {
        $this->_config = $instace;
        if (null !== $storeId) {
            $this->_config->setStoreId($storeId);
        }
        return $this;
    }

    /**
     * Config instance getter
     *
     * @return \Ebizmarts\SagePaySuite\Model\Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Instantiate and return info model
     *
     * @return \Ebizmarts\SagePaySuite\Model\Info
     */
    public function getInfo()
    {
        if (null === $this->_infoInstance) {
            $this->_infoInstance = $this->_infoFactory->create();
        }
        return $this->_infoInstance;
    }

    /**
     * Transfer transaction/payment information from API instance to order payment
     *
     * @param \Magento\Framework\Object|AbstractApi $from
     * @param \Magento\Payment\Model\InfoInterface $to
     * @return $this
     */
    public function importPaymentInfo(\Magento\Framework\Object $from, \Magento\Payment\Model\InfoInterface $to)
    {

    }

    /**
     * Void transaction
     *
     * @param \Magento\Framework\Object $payment
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Framework\Object $payment)
    {
    }

    /**
     * Attempt to capture payment
     * Will return false if the payment is not supposed to be captured
     *
     * @param \Magento\Framework\Object $payment
     * @param float $amount
     * @return false|null
     */
    public function capture(\Magento\Framework\Object $payment, $amount)
    {
    }

    /**
     * Refund a capture transaction
     *
     * @param \Magento\Framework\Object $payment
     * @param float $amount
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Framework\Object $payment, $amount)
    {

    }

    /**
     * Cancel payment
     *
     * @param \Magento\Framework\Object $payment
     * @return void
     */
    public function cancel(\Magento\Framework\Object $payment)
    {

    }

    /**
     * Fetch transaction details info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {

    }

    /**
     * Parent transaction id getter
     *
     * @param \Magento\Framework\Object $payment
     * @return string
     */
    protected function _getParentTransactionId(\Magento\Framework\Object $payment)
    {
        return $payment->getParentTransactionId();
    }
}
