<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\PI;

use Magento\Framework\Controller\ResultFactory;

class GenerateMerchantKey extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRest
     */
    private $_pirest;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirestapi
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirestapi
    ) {
    
        parent::__construct($context);
        $this->_pirest = $pirestapi;
    }

    public function execute()
    {
        try {
            $responseContent = [
                'success' => true,
                'merchant_session_key' => $this->_pirest->generateMerchantKey(),
            ];
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $responseContent = [
                'success' => false,
                'error_message' => __($apiException->getUserMessage()),
            ];
            $this->messageManager->addError(__($apiException->getUserMessage()));
        } catch (\Exception $e) {
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong: ' . $e->getMessage()),
            ];
            $this->messageManager->addError(__('Something went wrong: ' . $e->getMessage()));
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }
}
