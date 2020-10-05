<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Token;
use Ebizmarts\SagePaySuite\Model\Token\VaultDetailsHandler;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class Delete extends Action
{
    private $tokenId;

    private $isCustomerArea;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $suiteLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Token
     */
    private $tokenModel;

    /** @var VaultDetailsHandler */
    private $vaultDetailsHandler;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Delete constructor.
     * @param Context $context
     * @param Logger $suiteLogger
     * @param LoggerInterface $logger
     * @param Token $tokenModel
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        Logger $suiteLogger,
        LoggerInterface $logger,
        Token $tokenModel,
        Session $customerSession,
        VaultDetailsHandler $vaultDetailsHandler
    ) {
        parent::__construct($context);
        $this->suiteLogger         = $suiteLogger;
        $this->logger              = $logger;
        $this->tokenModel          = $tokenModel;
        $this->customerSession     = $customerSession;
        $this->isCustomerArea      = true;
        $this->vaultDetailsHandler = $vaultDetailsHandler;
    }

    /**
     * @return bool|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            //get token id
            if (!empty($this->getRequest()->getParam("token_id"))) {
                $this->tokenId = $this->getRequest()->getParam("token_id");
                if (!empty($this->getRequest()->getParam("checkout"))) {
                    $this->isCustomerArea = false;
                }
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to delete token: Invalid token id.'));
            }

            if ($this->vaultDetailsHandler->removeTokenFromVault($this->tokenId, $this->customerSession->getCustomerId())) {
                //prepare response
                $responseContent = [
                    'success' => true,
                    'response' => true
                ];
            } else {
                throw new \Magento\Framework\Validator\Exception(
                    __('Unable to delete token: Token is not owned by you')
                );
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);

            $responseContent = [
                'success' => false,
                'error_message' => __("Something went wrong: %1", $e->getMessage()),
            ];
        }

        if ($this->isCustomerArea == true) {
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
