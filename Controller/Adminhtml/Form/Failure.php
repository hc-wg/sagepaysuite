<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\Form;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Failure extends \Magento\Backend\App\AbstractAction
{
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
     * @param \Magento\Backend\App\Action\Context $context
     * @param Logger $suiteLogger
     * @param \Ebizmarts\SagePaySuite\Model\Form $formModel
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Model\Form $formModel
    ) {
    
        parent::__construct($context);
        $this->_suiteLogger = $suiteLogger;
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
            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $response);

            $statusDetail = $response["StatusDetail"];
            $statusDetail = explode(" : ", $statusDetail);
            $statusDetail = $statusDetail[1];

            $this->messageManager->addError($response["Status"] . ": " . $statusDetail);
            $this->_redirect('sales/order_create/index');

            return;
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_suiteLogger->logException($e);
        }
    }
}
