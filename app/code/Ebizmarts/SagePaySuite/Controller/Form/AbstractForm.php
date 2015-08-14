<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Quote\Api\CartManagementInterface;

class AbstractForm extends \Magento\Framework\App\Action\Action
{

    /**
     * Config mode type
     *
     * @var string
     */
    protected $_configType = 'Ebizmarts\SagePaySuite\Model\Config';

    /**
     * Config method type
     *
     * @var string
     */
    protected $_configMethod = \Ebizmarts\SagePaySuite\Model\Config::METHOD_FORM;

    /**
     * Checkout mode type
     *
     * @var string
     */
    protected $_checkoutType = 'Ebizmarts\SagePaySuite\Model\Form\Checkout';

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form\Checkout
     */
    protected $_checkout;

    /**
     * Internal cache of checkout models
     *
     * @var array
     */
    protected $_checkoutTypes = [];

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote = false;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Ebizmarts\SagePaySuite\Model\Form\Checkout\Factory
     */
    protected $_checkoutFactory;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $_sagepaysuiteSession;

    /**
     * @var \Magento\Framework\Url\Helper
     */
    protected $_urlHelper;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $_customerUrl;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $_cartManagement;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Ebizmarts\SagePaySuite\Model\Form\Checkout\Factory $checkoutFactory
     * @param \Magento\Framework\Session\Generic $sagepaysuiteSession
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Ebizmarts\SagePaySuite\Model\Form\Checkout\Factory $checkoutFactory,
        \Magento\Framework\Session\Generic $sagepaysuiteSession,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Customer\Model\Url $customerUrl,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Api\CartManagementInterface $cartManagement
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutFactory = $checkoutFactory;
        $this->_sagepaysuiteSession = $sagepaysuiteSession;
        $this->_urlHelper = $urlHelper;
        $this->_customerUrl = $customerUrl;
        $this->_logger = $logger;
        $this->_cartManagement = $cartManagement;

        parent::__construct($context);
        $this->_config = $this->_objectManager->create($this->_configType, []);
        $this->_config->setMethodCode($this->_configMethod);
    }

    /**
     * Instantiate quote and checkout
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initCheckout()
    {
        $quote = $this->_getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t initialize SagePay FORM Checkout.'));
        }
        if (!isset($this->_checkoutTypes[$this->_checkoutType])) {
            $parameters = [
                'params' => [
                    'quote' => $quote,
                    'config' => $this->_config,
                ],
            ];
            $this->_checkoutTypes[$this->_checkoutType] = $this->_checkoutFactory
                ->create($this->_checkoutType, $parameters);
        }
        $this->_checkout = $this->_checkoutTypes[$this->_checkoutType];
    }

    public function getFormModel()
    {
        //return $this->_formFactory->create('Ebizmarts\SagePaySuite\Model\Form',[]);
        return $this->_objectManager->get('Ebizmarts\SagePaySuite\Model\Form');
    }

    /**
     * SagePaySuite session instance getter
     *
     * @return \Magento\Framework\Session\Generic
     */
    protected function _getSession()
    {
        return $this->_sagepaysuiteSession;
    }

    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Return checkout quote object
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function _getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    /**
     * Returns login url parameter for redirect
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->_customerUrl->getLoginUrl();
    }

    /**
     * Redirect to login page
     *
     * @return void
     */
    public function redirectLogin()
    {
        $this->_actionFlag->set('', 'no-dispatch', true);
        $this->_customerSession->setBeforeAuthUrl($this->_redirect->getRefererUrl());
        $this->getResponse()->setRedirect(
            $this->_urlHelper->addRequestParam($this->_customerUrl->getLoginUrl(), ['context' => 'checkout'])
        );
    }

    /**
     * Returns action name which requires redirect
     * @return string
     */
    public function getRedirectActionName()
    {
        return 'start';
    }

    /**
     * Returns before_auth_url redirect parameter for customer session
     * @return null
     */
    public function getCustomerBeforeAuthUrl()
    {
        return;
    }

    /**
     * Returns a list of action flags [flag_key] => boolean
     * @return array
     */
    public function getActionFlagList()
    {
        return [];
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckout()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }
}