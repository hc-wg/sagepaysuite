<?php
/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Block\Payment;

/**
 * SagePay common payment info block
 * Uses default templates
 */
class Info extends \Magento\Payment\Block\Info\Cc
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\InfoFactory
     */
    protected $_sagepayInfoFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Ebizmarts\SagePaySuite\Model\InfoFactory $sagepayInfoFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Ebizmarts\SagePaySuite\Model\InfoFactory $sagepayInfoFactory,
        array $data = []
    ) {
        $this->_sagepayInfoFactory = $sagepayInfoFactory;
        parent::__construct($context, $paymentConfig, $data);
    }

    /**
     * Don't show CC type for non-CC methods
     *
     * @return string|null
     */
    public function getCcTypeName()
    {
        if (\Ebizmarts\SagePaySuite\Model\Config::getIsCreditCardMethod($this->getInfo()->getMethod())) {
            return parent::getCcTypeName();
        }
    }

    /**
     * Prepare SagePay-specific payment information
     *
     * @param \Magento\Framework\Object|array|null $transport
     * @return \Magento\Framework\Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();
        $sagepayInfo = $this->_sagepayInfoFactory->create();
        $info = $sagepayInfo->getPaymentInfo($payment, true);
        return $transport->addData($info);
    }
}
