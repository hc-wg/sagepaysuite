<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
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
    private $_order;

    /**
     * @var Config
     */
    private $_config;

    /**
     * @var \Ebizmarts\SagePaySuite\Helper\Data
     */
    private $_suiteHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        Config $config,
        array $data = []
    ) {
    
        $this->_order       = $registry->registry('current_order');
        $this->_config      = $config;
        $this->_suiteHelper = $suiteHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        return $this->_order->getPayment();
    }

    public function getTemplate()
    {
        $template = parent::getTemplate();

        $isSagePayMethod = $this->_config->isSagePaySuiteMethod($this->getPayment()->getMethod());

        if ($isSagePayMethod === false) {
            $template = '';
        }

        return $template;
    }

    public function getSyncFromApiUrl()
    {
        $url =  $this->getUrl('sagepaysuite/order/syncFromApi', ['order_id'=>$this->_order->getId()]);
        return $url;
    }

    /**
     * @return \Ebizmarts\SagePaySuite\Helper\Data
     */
    public function getSuiteHelper()
    {
        return $this->_suiteHelper;
    }
}
