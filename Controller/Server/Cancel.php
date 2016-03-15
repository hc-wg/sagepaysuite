<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Ebizmarts\SagePaySuite\Controller\Server;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Cancel extends \Magento\Framework\App\Action\Action
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
     * @param \Magento\Framework\App\Action\Context $context
     * @param Logger $suiteLogger
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        parent::__construct($context);
        $this->_suiteLogger = $suiteLogger;
        $this->_config = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_SERVER);
        $this->_logger = $logger;
        $this->_checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $message = $this->getRequest()->getParam("message");
        if(!empty($message)){
            $this->messageManager->addError($message);
        }
        $this->getResponse()->setBody(
            '<script>window.top.location.href = "'
            . $this->_url->getUrl('checkout/cart', array(
                '_secure' => true,
            ))
            . '";</script>'
        );
    }
}