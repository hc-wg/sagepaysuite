<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Type\Onepage;
use Ebizmarts\SagePaySuite\Model\ObjectLoader\OrderLoader;

class Success extends Action
{
    /** @var Config */
    private $config;

    /** @var Onepage */
    private $onepage;

    /** @var OrderLoader */
    private $orderLoader;

    /**
     * Callback3D constructor.
     * @param Context $context
     * @param Onepage $onepage
     * @param Config $config
     * @param OrderLoader $orderLoader
     */
    public function __construct(
        Context $context,
        Onepage $onepage,
        Config $config,
        OrderLoader $orderLoader
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->onepage = $onepage;
        $this->orderLoader = $orderLoader;
        $this->config->setMethodCode(Config::METHOD_PI);

    }

    public function execute()
    {
        $session = $this->onepage->getCheckout();
        $quoteId = $this->getRequest()->getParam("quoteId");
        $orderId = $this->getRequest()->getParam("orderId");
        if ($quoteId) {
            $session->setLastSuccessQuoteId($quoteId);
            $session->setLastQuoteId($quoteId);
        }

        if ($orderId) {
            $session->setLastOrderId($orderId);
            $order = $this->orderLoader->getById($orderId);
            $session->setLastRealOrderId($order->getIncrementId());
        }

        $session->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);
        $session->setData(\Ebizmarts\SagePaySuite\Model\Session::CONVERTING_QUOTE_TO_ORDER, 0);

        $this->_redirect("checkout/onepage/success");
    }
}
