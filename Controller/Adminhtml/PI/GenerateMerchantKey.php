<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Adminhtml\PI;

use Magento\Framework\Controller\ResultFactory;

class GenerateMerchantKey extends \Magento\Backend\App\AbstractAction
{
    /** @var \Ebizmarts\SagePaySuite\Model\PiMsk */
    private $piMsk;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\PiMsk $piMsk
    ) {
    
        parent::__construct($context);
        $this->piMsk = $piMsk;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Ebizmarts\SagePaySuite\Api\Data\ResultInterface $result */
        $result = $this->piMsk->getSessionKey();

        if ($result->getSuccess() === false) {
            $this->messageManager->addError(__('Something went wrong: ' . $result->getErrorMessage()));
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result->__toArray());
        return $resultJson;
    }
}
