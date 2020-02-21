<?php

namespace Ebizmarts\SagePaySuite\Controller\Cart;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Ebizmarts\SagePaySuite\Model\RecoverCartAndCancelOrder;

class Recover extends Action
{
    /** @var RecoverCartAndCancelOrder */
    private $recoverCartAndCancelOrder;

    /**
     * Recover constructor.
     * @param Context $context
     * @param RecoverCartAndCancelOrder $recoverCartAndCancelOrder
     */
    public function __construct(
        Context $context,
        RecoverCartAndCancelOrder $recoverCartAndCancelOrder
    ) {
        parent::__construct($context);
        $this->recoverCartAndCancelOrder = $recoverCartAndCancelOrder;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $this->recoverCartAndCancelOrder->execute(false);
        return $this->redirectToCart();
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    private function redirectToCart()
    {
        $redirectUrl = 'checkout/cart';
        return $this->_redirect($redirectUrl);
    }
}
