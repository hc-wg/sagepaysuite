<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\PI;


use Magento\Framework\Controller\ResultFactory;


class Callback3D extends \Magento\Framework\App\Action\Action
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


    }
}
