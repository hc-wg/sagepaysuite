<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\Reports\Tokens;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;


class Delete extends \Magento\Backend\App\Action
{

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Token
     */
    protected $_tokenModel;

    protected $_tokenId;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param Logger $suiteLogger
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Ebizmarts\SagePaySuite\Model\Token $tokenModel
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Logger $suiteLogger,
        \Psr\Log\LoggerInterface $logger,
        \Ebizmarts\SagePaySuite\Model\Token $tokenModel
    )
    {
        parent::__construct($context);
        $this->_suiteLogger = $suiteLogger;
        $this->_logger = $logger;
        $this->_tokenModel = $tokenModel;
    }

    public function execute()
    {
        try {

            $this->_view->loadLayout();
            $this->_tokenId = $this->getRequest()->getParam('id');

            if (empty($this->_tokenId)) {
                throw new \Magento\Framework\Validator\Exception(__('Unable to delete token: Invalid token id.'));
            }

            $token = $this->_tokenModel->loadToken($this->_tokenId);

            //delete
            $token->deleteToken();

            $this->messageManager->addSuccess(__('Token deleted successfully.'));

        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {

            $this->_logger->critical($apiException);
            $this->messageManager->addError(__($apiException->getUserMessage()));

        } catch (\Exception $e) {

            $this->_logger->critical($e);
            $this->messageManager->addError(__($e->getMessage()));
        }

        $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        return true;
    }
}
