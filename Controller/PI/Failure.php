<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\ObjectLoader\OrderLoader;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class Failure extends Action
{
    /** @var Config */
    private $config;

    /** @var Onepage */
    private $onepage;

    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /** @var RecoverCart */
    private $recoverCart;

    /**
     * Failure constructor.
     * @param Context $context
     * @param Onepage $onepage
     * @param Config $config
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Context $context,
        Onepage $onepage,
        Config $config,
        CartRepositoryInterface $quoteRepository,
        RecoverCart $recoverCart
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->onepage = $onepage;
        $this->quoteRepository = $quoteRepository;
        $this->recoverCart = $recoverCart;
        $this->config->setMethodCode(Config::METHOD_PI);
    }

    public function execute()
    {
        $session = $this->onepage->getCheckout();
        $params = $this->getRequest()->getParams();
        if (isset($params['orderId']) && $params['orderId'] !== null) {
            $this->recoverCart->setShouldCancelOrder(true)->setOrderId($params['orderId'])->execute();
        } elseif (isset($params['quoteId']) && $params['quoteId'] !== null) {
            $session->setQuoteId((int)$params['quoteId']);
            $quote = $this->quoteRepository->get($params['quoteId']);
            $session->replaceQuote($quote);
        }
        $session->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);
        $session->setData(\Ebizmarts\SagePaySuite\Model\Session::CONVERTING_QUOTE_TO_ORDER, 0);

        if (!empty($params['errorMessage'])) {
            $this->addErrorMessage($params['errorMessage']);
        }

        $this->_redirect("checkout/cart");
    }

    /**
     * @param $errorMessage
     */
    public function addErrorMessage(string $errorMessage)
    {
        $this->messageManager->addError(urldecode($errorMessage));
    }
}
