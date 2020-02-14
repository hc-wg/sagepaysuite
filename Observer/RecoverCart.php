<?php


namespace Ebizmarts\SagePaySuite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Theme\Block\Html\Header\Logo;

class RecoverCart implements ObserverInterface
{
    /** @var Session */
    private $session;

    /** @var Logger */
    private $suiteLogger;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var Logo */
    private $logo;

    /**
     * RecoverCart constructor.
     * @param Session $session
     * @param Logger $suiteLogger
     * @param ManagerInterface $messageManager
     * @param Logo $logo
     */
    public function __construct(
        Session $session,
        Logger $suiteLogger,
        ManagerInterface $messageManager,
        Logo $logo
    ) {
        $this->session = $session;
        $this->suiteLogger     = $suiteLogger;
        $this->messageManager  = $messageManager;
        $this->logo            = $logo;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->isHomePage($observer)) {
            $presavedOrderId = $this->session->getData(SagePaySession::PRESAVED_PENDING_ORDER_KEY);
            $quoteIsActive = $this->session->getData(SagePaySession::QUOTE_IS_ACTIVE);
            if ($this->checkIfRecoverCartIsPossible($presavedOrderId, $quoteIsActive)) {
                $message = "There's an order in process, but you can recover the cart ";
                $message .= sprintf("<a target='_self' href='http://m233.local/sagepaysuite/cart/recover'>%s</a>", __('HERE'));
                $this->messageManager->addNotice($message);
            }
        }
    }

    /**
     * @return bool
     */
    public function isHomePage()
    {
        return $this->logo->isHomePage();
    }

    /**
     * @param $presavedOrderId
     * @param $quoteIsActive
     * @return bool
     */
    public function checkIfRecoverCartIsPossible($presavedOrderId, $quoteIsActive)
    {
        return $this->checkPreSavedOrder($presavedOrderId) && $this->checkQuoteIsActive($quoteIsActive);
    }

    /**
     * @param $presavedOrderId
     * @return bool
     */
    public function checkPreSavedOrder($presavedOrderId)
    {
        return !empty($presavedOrderId);
    }

    /**
     * @param $quoteIsActive
     * @return bool
     */
    public function checkQuoteIsActive($quoteIsActive)
    {
        return $quoteIsActive === 0;
    }
}
