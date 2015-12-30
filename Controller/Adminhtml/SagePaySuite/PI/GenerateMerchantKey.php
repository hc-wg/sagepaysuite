<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\SagePaySuite\PI;


use Magento\Framework\Controller\ResultFactory;


class GenerateMerchantKey extends Magento\Backend\App\AbstractAction
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRestApi
     */
    protected $_pirest;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Api\PIRestApi $pirest
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Api\PIRestApi $pirest
    )
    {
        parent::__construct($context);

        $this->_pirest = $pirest;
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
                'error_message' => __('Something went wrong while generating the merchant session key.'),
            ];
            $this->messageManager->addError(__('Something went wrong while generating the merchant session key.'));
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }
}
