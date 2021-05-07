<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Model\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Type\Onepage;

class Success extends Action implements CsrfAwareActionInterface
{
    /** @var Config */
    private $config;

    /** @var Onepage */
    private $onepage;

    /**
     * Callback3D constructor.
     * @param Context $context
     * @param Onepage $onepage
     * @param Config $config
     */
    public function __construct(
        Context $context,
        Onepage $onepage,
        Config $config
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->onepage = $onepage;
        $this->config->setMethodCode(Config::METHOD_PI);
    }

    public function execute()
    {
        $session = $this->onepage->getCheckout();
        $quoteId = $this->getRequest()->getParam("quoteId");
        $session->setLastSuccessQuoteId($quoteId);


        error_log(__METHOD__."\n", 3, '/Users/Santiago/Sites/opayo-234/var/log/ebizmarts.log');
        error_log($this->_objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()."\n", 3, '/Users/Santiago/Sites/opayo-234/var/log/ebizmarts.log');

        $session = $this->onepage->getCheckout();
        error_log($session->getLastOrderId()."\n", 3, '/Users/Santiago/Sites/opayo-234/var/log/ebizmarts.log');
        error_log("lastRealOrder\n", 3, '/Users/Santiago/Sites/opayo-234/var/log/ebizmarts.log');
        error_log(json_encode($session->getLastRealOrder()->getData(),JSON_PRETTY_PRINT)."\n", 3, '/Users/Santiago/Sites/opayo-234/var/log/ebizmarts.log');
        error_log("quote\n", 3, '/Users/Santiago/Sites/opayo-234/var/log/ebizmarts.log');
        error_log(json_encode($session->getQuote()->getData(),JSON_PRETTY_PRINT)."\n", 3, '/Users/Santiago/Sites/opayo-234/var/log/ebizmarts.log');

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
