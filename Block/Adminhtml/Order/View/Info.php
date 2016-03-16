<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Adminhtml\Order\View;

use Ebizmarts\SagePaySuite\Model\Config;

/**
 * Backend order view block for Sage Pay payment information
 *
 * @package Ebizmarts\SagePaySuite\Block\Adminhtml\Order\View
 */
class Info extends \Magento\Backend\Block\Template
{

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        Config $config,
        array $data = []
    )
    {
        $this->_order = $registry->registry('current_order');;
        $this->_config = $config;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        return $this->_order->getPayment();
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        return $this->_config->isSagePaySuiteMethod($this->getPayment()->getMethod()) ? parent::_toHtml() : '';
    }

    public function getSyncFromApiUrl()
    {
        $url =  $this->getUrl('sagepaysuite/order/syncFromApi',array('order_id'=>$this->_order->getId()));
        return $url;
    }
}