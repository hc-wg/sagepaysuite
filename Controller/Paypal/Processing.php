<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Paypal;

use Magento\Framework\App\Action\Action;

class Processing extends Action
{
    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        $body = $this->_view->getLayout()->createBlock(
            \Ebizmarts\SagePaySuite\Block\Paypal\Processing::class
        )
        ->setData(
            ["paypal_post"=>$this->getRequest()->getPost()]
        )->toHtml();

        $this->getResponse()->setBody($body);
    }
}
