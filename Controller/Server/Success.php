<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Server;


use Ebizmarts\SagePaySuite\Model\Logger\Logger;


class Success extends \Magento\Framework\App\Action\Action
{

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Api\PIRestApi $pirest
     * @param Logger $suiteLogger
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        parent::__construct($context);

        $this->_suiteLogger = $suiteLogger;
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
        $this->_logger = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
    }

    public function execute()
    {
        try {

            $quote = $this->_objectManager->get('\Magento\Quote\Model\Quote')->load($this->getRequest()->getParam("quoteid"));
            $order = $this->_orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());

            //prepare session to success page
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId());
            $this->_checkoutSession->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        //redirect to success via javascript
        $this->getResponse()->setBody(
            '<script>window.top.location.href = "'
            . $this->_url->getUrl('checkout/onepage/success', array(
                '_secure' => true,
                //'_store' => $this->getRequest()->getParam('_store')
            ))
            . '";</script>'
        );
    }
}
