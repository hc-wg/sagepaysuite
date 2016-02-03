<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Failure extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * Checkout data
     *
     * @var \Magento\Checkout\Helper\Data
     */
    protected $_checkoutData;

    protected $_quote;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        OrderSender $orderSender,
        \Psr\Log\LoggerInterface $logger
    )
    {
        parent::__construct($context);
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM);
        $this->_checkoutData = $checkoutData;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->_logger = $logger;
    }

    /**
     * FORM success callback
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {

            $this->_redirect('checkout/onepage/failure');

            return;

        } catch (\Exception $e) {
            //$this->messageManager->addError(__('We can\'t place the order. Please try again.'));
            $this->_logger->critical($e);
            //$this->_redirect('*/*/review');
            //$this->_redirectToCartAndShowError('We can\'t place the order. Please try again.');
        }
    }
}
