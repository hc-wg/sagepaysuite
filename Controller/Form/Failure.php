<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Failure extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form
     */
    protected $_formModel;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param Logger $suiteLogger
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Ebizmarts\SagePaySuite\Model\Form $formModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Psr\Log\LoggerInterface $logger,
        \Ebizmarts\SagePaySuite\Model\Form $formModel
    ) {
    
        parent::__construct($context);
        $this->_suiteLogger = $suiteLogger;
        $this->_logger = $logger;
        $this->_formModel = $formModel;
    }

    /**
     * @throws Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            //decode response
            $response = $this->_formModel->decodeSagePayResponse($this->getRequest()->getParam("crypt"));
            if (!array_key_exists("Status", $response) || !array_key_exists("StatusDetail", $response)) {
                throw new \Magento\Framework\Exception\LocalizedException('Invalid response from Sage Pay');
            }

            //log response
            $this->_suiteLogger->SageLog(Logger::LOG_REQUEST, $response);

            $statusDetail = $response["StatusDetail"];
            $statusDetail = explode(" : ", $statusDetail);
            $statusDetail = $statusDetail[1];

            $this->messageManager->addError($response["Status"] . ": " . $statusDetail);
            $this->_redirect('checkout/cart');

            return;
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_logger->critical($e);
        }
    }
}
