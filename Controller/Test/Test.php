<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Test;



class Test extends \Magento\Framework\App\Action\Action
{

    protected $cron;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Cron $cron
    ){
        parent::__construct($context);

        $this->cron = $cron;
    }

    public function execute()
    {

        //$this->cron->checkFraud();
        $this->cron->cancelPendingPaymentOrders();
    }
}
