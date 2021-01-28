<?php

namespace Ebizmarts\SagePaySuite\Helper;

use Psr\Log\LoggerInterface as Logger;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;

class CustomerLogin
{
    /** @var Logger */
    private $logger;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var CustomerRepositoryInterface */
    private $customerSession;

    /**
     * CustomerLogin constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param Logger $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        Logger $logger)
    {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
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
            $this->logger->critical($e);
        }
    }
}
