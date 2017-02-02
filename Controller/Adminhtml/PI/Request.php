<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\PI;

use Magento\Framework\Controller\ResultFactory;

class Request extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    private $_config;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $_quoteSession;

    /** @var \Ebizmarts\SagePaySuite\Model\PiRequestManagement\MotoManagement */
    private $requester;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager */
    private $piRequestManagerDataFactory;

    /**
     * Request constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Ebizmarts\SagePaySuite\Model\PiRequestManagement\MotoManagement $requester
     * @param \Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory $piReqManagerFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Ebizmarts\SagePaySuite\Model\PiRequestManagement\MotoManagement $requester,
        \Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory $piReqManagerFactory
    ) {
        parent::__construct($context);
        $this->_config                     = $config;
        $this->_quoteSession    = $quoteSession;
        $this->_quote           = $this->_quoteSession->getQuote();

        $this->requester                   = $requester;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
    }

    public function execute()
    {
        /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager $data */
        $data = $this->piRequestManagerDataFactory->create();
        $data->setMode($this->_config->getMode());
        $data->setVendorName($this->_config->getVendorname());
        $data->setPaymentAction($this->_config->getSagepayPaymentAction());
        $data->setMerchantSessionKey($this->getRequest()->getPost('merchant_session_key'));
        $data->setCardIdentifier($this->getRequest()->getPost('card_identifier'));
        $data->setCcExpMonth($this->getRequest()->getPost('card_exp_month'));
        $data->setCcExpYear($this->getRequest()->getPost('card_exp_year'));
        $data->setCcLastFour($this->getRequest()->getPost('card_last4'));
        $data->setCcType($this->getRequest()->getPost('card_type'));

        $this->requester->setRequestData($data);
        $this->requester->setQuote($this->_quote);

        $response = $this->requester->placeOrder();

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($response->__toArray());
        return $resultJson;
    }
}
