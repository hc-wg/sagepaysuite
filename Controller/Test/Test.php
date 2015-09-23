<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Test;

use Ebizmarts\SagePaySuite\Model\Api\Transaction;
use Magento\Framework\Webapi\Exception;


class Test extends \Magento\Framework\App\Action\Action
{

    protected $_sagepay_transaction;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Ebizmarts\SagePaySuite\Model\Api\Transaction $sp_transaction
    ){
        parent::__construct($context);

        $this->_sagepay_transaction = $sp_transaction;
    }

    public function execute()
    {

        try {
            $result = $this->_sagepay_transaction->getTransactionDetails("AAAAAAAA");
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {

            $error_message = $apiException->getUserMessage();
        }
    }
}
