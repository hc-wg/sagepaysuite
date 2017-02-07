<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Token;

use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Delete extends \Magento\Framework\App\Action\Action
{

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Token
     */
    private $_tokenModel;

    private $_tokenId;
    private $_isCustomerArea;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * Delete constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param Logger $suiteLogger
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Ebizmarts\SagePaySuite\Model\Token $tokenModel
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $suiteLogger,
        \Psr\Log\LoggerInterface $logger,
        \Ebizmarts\SagePaySuite\Model\Token $tokenModel,
        \Magento\Customer\Model\Session $customerSession
    ) {
    
        parent::__construct($context);
        $this->_suiteLogger     = $suiteLogger;
        $this->_logger          = $logger;
        $this->_tokenModel      = $tokenModel;
        $this->_customerSession = $customerSession;
        $this->_isCustomerArea  = true;
    }

    public function execute()
    {
        try {
            //get token id
            if (!empty($this->getRequest()->getParam("token_id"))) {
                $this->_tokenId = $this->getRequest()->getParam("token_id");
                if (!empty($this->getRequest()->getParam("checkout"))) {
                    $this->_isCustomerArea = false;
                }
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to delete token: Invalid token id.'));
            }

            $token = $this->_tokenModel->loadToken($this->_tokenId);

            //validate ownership
            if ($token->isOwnedByCustomer($this->_customerSession->getCustomerId())) {
                //delete
                $token->deleteToken();
            } else {
                throw new \Magento\Framework\Validator\Exception(
                    __('Unable to delete token: Token is not owned by you')
                );
            }

            //prepare response
            $responseContent = [
                'success' => true,
                'response' => true
            ];
        } catch (\Exception $e) {
            $this->_logger->critical($e);

            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $e->getMessage()),
            ];
        }

        if ($this->_isCustomerArea == true) {
            if ($responseContent["success"] == true) {
                $this->messageManager->addSuccess(__('Token deleted successfully.'));
                $this->_redirect('sagepaysuite/customer/tokens');
                return true;
            } else {
                $this->messageManager->addError(__($responseContent["error_message"]));
                $this->_redirect('sagepaysuite/customer/tokens');
                return false;
            }
        } else {
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($responseContent);
            return $resultJson;
        }
    }
}
