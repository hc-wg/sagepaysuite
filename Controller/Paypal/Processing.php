<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Paypal;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Processing extends \Magento\Framework\App\Action\Action
{

    /**
     * Success constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\SagePaySuite\Model\Config $config
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param OrderSender $orderSender
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    )
    {
        parent::__construct($context);
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        $body = $this->_view->getLayout()->createBlock(
            'Ebizmarts\SagePaySuite\Block\Paypal\Processing')
            ->setData(
                ["paypal_post"=>$this->getRequest()->getPost()]
            )->toHtml();

        $this->getResponse()->setBody($body);
    }
}
