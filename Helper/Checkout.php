<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Helper;

use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Checkout extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Checkout data
     * @var \Magento\Checkout\Helper\Data
     */
    protected $_checkoutData;

    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $_objectCopyService;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $_dataObjectHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    protected $_suiteLogger;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param OrderSender $orderSender
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Logger $suiteLogger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        OrderSender $orderSender,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Checkout\Model\Session $checkoutSession,
        Logger $suiteLogger
    )
    {
        parent::__construct($context);
        $this->_quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->_customerSession = $customerSession;
        $this->_checkoutData = $checkoutData;
        $this->_objectCopyService = $objectCopyService;
        $this->_customerRepository = $customerRepository;
        $this->_dataObjectHelper = $dataObjectHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->_suiteLogger = $suiteLogger;
        $this->_quote = $this->_checkoutSession->getQuote();
    }

    /**
     * Place order manually from default checkout
     *
     * @return \Magento\Sales\Model\Order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function placeOrder()
    {

        switch ($this->_getCheckoutMethod()) {
            case \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST:
                $this->_prepareGuestQuote();
                break;
            case \Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER:
                $this->_prepareNewCustomerQuote();
                break;
            default:
                $this->_prepareCustomerQuote();
                break;
        }

        $this->_quote->collectTotals();

        $order = $this->_quoteManagement->submit($this->_quote);

        if (!$order) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Can not save order. Please try another payment option.'));
        }

        return $order;
    }

    /**
     * Get checkout method
     *
     * @return string
     */
    protected function _getCheckoutMethod()
    {
        if ($this->_customerSession->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }
        if (!$this->_quote->getCheckoutMethod()) {
            if ($this->_checkoutData->isAllowedGuestCheckout($this->_quote)) {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }
        return $this->_quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @return $this
     */
    protected function _prepareGuestQuote()
    {
        $quote = $this->_quote;

        $quote->setCustomerId(null);
        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);

        return $this;
    }

    /**
     * Prepare quote for customer registration and customer order submit
     *
     * @return void
     */
    protected function _prepareNewCustomerQuote()
    {
        $quote = $this->_quote;
        $billing = $quote->getBillingAddress();
        $shipping = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $quote->getCustomer();
        $customerBillingData = $billing->exportCustomerAddress();
        $dataArray = $this->_objectCopyService->getDataFromFieldset('checkout_onepage_quote', 'to_customer', $quote);
        $this->_dataObjectHelper->populateWithArray(
            $customer,
            $dataArray,
            '\Magento\Customer\Api\Data\CustomerInterface'
        );
        $quote->setCustomer($customer);
        $quote->setCustomerId(true);

        $customerBillingData->setIsDefaultBilling(true);

        if ($shipping) {
            if (!$shipping->getSameAsBilling()) {
                $customerShippingData = $shipping->exportCustomerAddress();
                $customerShippingData->setIsDefaultShipping(true);
                $shipping->setCustomerAddressData($customerShippingData);
                // Add shipping address to quote since customer Data Object does not hold address information
                $quote->addCustomerAddress($customerShippingData);
            } else {
                $shipping->setCustomerAddressData($customerBillingData);
                $customerBillingData->setIsDefaultShipping(true);
            }
        } else {
            $customerBillingData->setIsDefaultShipping(true);
        }
        $billing->setCustomerAddressData($customerBillingData);

        // Add billing address to quote since customer Data Object does not hold address information
        $quote->addCustomerAddress($customerBillingData);
    }

    /**
     * Prepare quote for customer order submit
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _prepareCustomerQuote()
    {
        $quote = $this->_quote;
        $billing = $quote->getBillingAddress();
        $shipping = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $this->_customerRepository->getById($this->_customerSession->getCustomerId());
        $hasDefaultBilling = (bool)$customer->getDefaultBilling();
        $hasDefaultShipping = (bool)$customer->getDefaultShipping();

        if ($shipping && !$shipping->getSameAsBilling() &&
            (!$shipping->getCustomerId() || $shipping->getSaveInAddressBook())
        ) {
            $shippingAddress = $shipping->exportCustomerAddress();
            if (!$hasDefaultShipping) {
                //Make provided address as default shipping address
                $shippingAddress->setIsDefaultShipping(true);
                $hasDefaultShipping = true;
            }
            $quote->addCustomerAddress($shippingAddress);
            $shipping->setCustomerAddressData($shippingAddress);
        }

        if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
            $billingAddress = $billing->exportCustomerAddress();
            if (!$hasDefaultBilling) {
                //Make provided address as default shipping address
                if (!$hasDefaultShipping) {
                    //Make provided address as default shipping address
                    $billingAddress->setIsDefaultShipping(true);
                }
                $billingAddress->setIsDefaultBilling(true);
            }
            $quote->addCustomerAddress($billingAddress);
            $billing->setCustomerAddressData($billingAddress);
        }
    }

    /**
     * @param $order
     */
    public function sendOrderEmail($order)
    {
        $this->orderSender->send($order);
    }

}
