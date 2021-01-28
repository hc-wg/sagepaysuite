<?php
declare(strict_types=1);

namespace Ebizmarts\SagePaySuite\Helper;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;

class CustomerLogin
{
    /** @var Logger */
    private $suiteLogger;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var CustomerRepositoryInterface */
    private $customerSession;

    /**
     * CustomerLogin constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param Logger $suiteLogger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        Logger $suiteLogger)
    {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->suiteLogger = $suiteLogger;
    }

    /**
     * @param $customerId
     */
    public function logInCustomer($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $this->customerSession->setCustomerDataAsLoggedIn($customer);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getTraceAsString(), [__METHOD__, __LINE__]);
        }
    }
}
