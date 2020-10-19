<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Token;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Token;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class Delete extends Action
{

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

    private $tokenId;
    private $paymentMethod;
    private $isCustomerArea;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Token\VaultDetailsHandler
     */
    private $vaultDetailsHandler;

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
        Token\VaultDetailsHandler $vaultDetailsHandler
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
            if (
                !empty($this->getRequest()->getParam("token_id"))
            ) {
                $this->tokenId = $this->getRequest()->getParam("token_id");
                if (!empty($this->getRequest()->getParam("checkout"))) {
                    $this->isCustomerArea = false;
                    $this->paymentMethod = $this->getRequest()->getParam('pmethod');
                } else {
                    $isv = $this->getRequest()->getParam('isv');
                    if (isset($isv) && $isv) {
                        $this->paymentMethod = 'sagepaysuitepi';
                    } else {
                        $this->paymentMethod = 'sagepaysuiteserver';
                    }
                }
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to delete token: Invalid token id.'));
            }

            // This if is temporary, once server start using vault the if for server should be removed
            // and delete the token the same way as pi.
            if ($this->paymentMethod === 'sagepaysuiteserver') {
                $token = $this->tokenModel->loadToken($this->tokenId);

                //validate ownership
                if ($token->isOwnedByCustomer($this->customerSession->getCustomerId())) {
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
            } elseif ($this->paymentMethod === 'sagepaysuitepi') {
                if ($this->vaultDetailsHandler->deleteToken($this->tokenId, $this->customerSession->getCustomerId())) {
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
