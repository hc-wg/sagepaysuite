<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\PI;


use Magento\Framework\Controller\ResultFactory;


class GenerateMerchantKey extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Ebizmarts\SagePaySuite\Model\Api\PIRest
     */
    protected $_pirestapi;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirest
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirestapi
    )
    {
        parent::__construct($context);
        $this->_pirestapi = $pirestapi;
    }

    public function execute()
    {
        try {

            $responseContent = [
                'success' => true,
                'merchant_session_key' => $this->_pirestapi->generateMerchantKey()
            ];
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException)
        {
            $responseContent = [
                'success' => false,
                'error_message' => __($apiException->getUserMessage())
            ];
        } catch (\Exception $e)
        {
            $responseContent = [
                'success' => false,
                'error_message' => __('Something went wrong while generating the merchant session key.')
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }
}
