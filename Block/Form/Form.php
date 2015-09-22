<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Block\Form;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\ConfigFactory;

class Form extends \Magento\Payment\Block\Form
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_methodCode = Config::METHOD_FORM;

    /**
     * SagePay data
     *
     * @var Data
     */
    protected $_sagepayData;

    /**
     * @var ConfigFactory
     */
    protected $_sagepayConfigFactory;

    /**
     * @param Context $context
     * @param ConfigFactory $sagepayConfigFactory
     * @param Data $sagepayData
     * @param CurrentCustomer $currentCustomer
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigFactory $sagepayConfigFactory,
        Data $sagepayData,
        CurrentCustomer $currentCustomer,
        array $data = []
    ) {
        $this->_sagepayData = $sagepayData;
        $this->_sagepayConfigFactory = $sagepayConfigFactory;
        $this->currentCustomer = $currentCustomer;
        parent::__construct($context, $data);
    }

    /**
     * Set template and redirect message
     *
     * @return null
     */
    protected function _construct()
    {
        $this->_config = $this->_sagepayConfigFactory->create()
            ->setMethod($this->getMethodCode());

        parent::_construct();
    }

    /**
     * Payment method code getter
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_methodCode;
    }

}
