<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\ObjectLoader\OrderLoader;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Success extends Action implements CsrfAwareActionInterface
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

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
