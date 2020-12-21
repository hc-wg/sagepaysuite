<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Token;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\Token;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\CouldNotDeleteException;
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

    /** @var int */
    private $tokenId;

    /** @var string */
    private $paymentMethod;

    /** @var bool */
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
            if (!empty($this->getRequest()->getParam("token_id"))) {
                $this->tokenId = $this->getRequest()->getParam("token_id");
                if (!empty($this->getRequest()->getParam("checkout"))) {
                    $this->isCustomerArea = false;
                    $this->paymentMethod = $this->getRequest()->getParam('pmethod');
                } else {
                    $isVault = $this->getRequest()->getParam('isVault');
                    if (isset($isVault) && $isVault) {
                        $this->paymentMethod = Config::METHOD_PI;
                    } else {
                        $this->paymentMethod = Config::METHOD_SERVER;
                    }
                }
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Unable to delete token: Invalid token id.'));
            }

            // This if is temporary, once server start using vault the if for server should be removed
            // and delete the token the same way as pi.
            if ($this->paymentMethod === Config::METHOD_SERVER) {
                $token = $this->tokenModel->loadToken($this->tokenId);

                //validate ownership
                if ($token->isOwnedByCustomer($this->customerSession->getCustomerId())) {
                    //delete
                    $token->deleteToken();
                } else {
                    throw new AuthenticationException(
                        __('Unable to delete token: Token is not owned by you')
                    );
                }

                //prepare response
                $responseContent = [
                    'success' => true,
                    'response' => true
                ];
            } elseif ($this->paymentMethod === Config::METHOD_PI) {
                if ($this->vaultDetailsHandler->deleteToken($this->tokenId, $this->customerSession->getCustomerId())) {
                    //prepare response
                    $responseContent = [
                        'success' => true,
                        'response' => true
                    ];
                } else {
                    throw new CouldNotDeleteException(
                        __('Unable to delete token')
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
                $this->addSuccessMessage();
                $this->_redirect('sagepaysuite/customer/tokens');
                return true;
            } else {
                $this->messageManager->addError(__($responseContent["error_message"]));
                $this->_redirect('sagepaysuite/customer/tokens');
                return false;
            }
        } else {
            $resultJson = $this->getResultFactory();
            $resultJson->setData($responseContent);
            return $resultJson;
        }
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function getResultFactory()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_JSON);
    }

    public function addSuccessMessage()
    {
        $this->messageManager->addSuccess(__('Token deleted successfully.'));
    }
}
