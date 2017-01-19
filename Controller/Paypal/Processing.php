<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Paypal;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Processing extends \Magento\Framework\App\Action\Action
{

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        $body = $this->_view->getLayout()->createBlock(
            'Ebizmarts\SagePaySuite\Block\Paypal\Processing'
        )
            ->setData(
                ["paypal_post"=>$this->getRequest()->getPost()]
            )->toHtml();

        $this->getResponse()->setBody($body);
    }
}
