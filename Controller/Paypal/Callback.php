<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Paypal;

use Ebizmarts\SagePaySuite\Helper\Data as SuiteHelper;
use Ebizmarts\SagePaySuite\Helper\RepositoryQuery;
use Ebizmarts\SagePaySuite\Model\Api\Post;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Klarna\Kp\Model\QuoteRepository;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Quote\Model\Quote;
use Magento\Framework\Encryption\EncryptorInterface;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Magento\Sales\Model\OrderRepository;

class Callback extends Action
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $suiteLogger;

    private $postData;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var Post */
    private $postApi;

    /** @var \Magento\Sales\Model\Order */
    private $order;

    /** @var OrderUpdateOnCallback */
    private $updateOrderCallback;

    /** @var SuiteHelper */
    private $suiteHelper;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /** @var RecoverCart */
    private $recoverCart;

    /**
     * @var RepositoryQuery
     */
    private $_repositoryQuery;

    /**
     * Callback constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param Config $config
     * @param Logger $suiteLogger
     * @param Post $postApi
     * @param Quote $quote
     * @param OrderRepository $orderRepository
     * @param QuoteRepository $quoteRepository
     * @param OrderUpdateOnCallback $updateOrderCallback
     * @param SuiteHelper $suiteHelper
     * @param EncryptorInterface $encryptor
     * @param RecoverCart $recoverCart
     * @param RepositoryQuery $repositoryQuery
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $config,
        Logger $suiteLogger,
        Post $postApi,
        Quote $quote,
        OrderRepository $orderRepository,
        QuoteRepository $quoteRepository,
        OrderUpdateOnCallback $updateOrderCallback,
        SuiteHelper $suiteHelper,
        EncryptorInterface $encryptor,
        RecoverCart $recoverCart,
        RepositoryQuery $repositoryQuery
    ) {
    
        parent::__construct($context);
        $this->config               = $config;
        $this->checkoutSession      = $checkoutSession;
        $this->suiteLogger          = $suiteLogger;
        $this->postApi              = $postApi;
        $this->quote                = $quote;
        $this->orderRepository      = $orderRepository;
        $this->quoteRepository      = $quoteRepository;
        $this->updateOrderCallback  = $updateOrderCallback;
        $this->suiteHelper          = $suiteHelper;
        $this->encryptor            = $encryptor;
        $this->recoverCart          = $recoverCart;
        $this->_repositoryQuery     = $repositoryQuery;
        $this->config->setMethodCode(Config::METHOD_PAYPAL);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            //get POST data
            $this->postData = $this->getRequest()->getPost();

            //log response
            $this->suiteLogger->sageLog(Logger::LOG_REQUEST, $this->postData, [__METHOD__, __LINE__]);

            $this->validatePostDataStatusAndStatusDetail();

            $this->loadQuoteFromDataSource();

            $order = $this->loadOrderFromDataSource();

            $completionResponse = $this->sendCompletionPost()["data"];

            $transactionId = $completionResponse["VPSTxId"];
            $transactionId = $this->suiteHelper->removeCurlyBraces($transactionId);

            $payment = $order->getPayment();

            $this->updatePaymentInformation($transactionId, $payment, $completionResponse);

            $this->updateOrderCallback->setOrder($this->order);
            $this->updateOrderCallback->confirmPayment($transactionId);

            //prepare session to success or cancellation page
            $this->checkoutSession->clearHelperData();
            $this->checkoutSession->setLastQuoteId($this->quote->getId());
            $this->checkoutSession->setLastSuccessQuoteId($this->quote->getId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());
            $this->checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);

            $this->_redirect('checkout/onepage/success');

            return;
        } catch (\Exception $e) {
            $this->recoverCart->setShouldCancelOrder(true)->execute();
            $this->suiteLogger->logException($e);
            $this->redirectToCartAndShowError('We can\'t place the order: ' . $e->getMessage());
        }
    }

    private function sendCompletionPost()
    {
        $request = [
            "VPSProtocol" => $this->config->getVPSProtocol(),
            "TxType"      => "COMPLETE",
            "VPSTxId"     => $this->postData->VPSTxId,
            "Amount"      => $this->getAuthorisedAmount(),
            "Accept"      => "YES"
        ];

        return $this->postApi->sendPost(
            $request,
            $this->getServiceURL(),
            ["OK", 'REGISTERED', 'AUTHENTICATED'],
            'Invalid response from PayPal'
        );
    }

    private function getAuthorisedAmount()
    {
        $quoteAmount = $this->config->getQuoteAmount($this->quote);
        $amount = number_format($quoteAmount, 2, '.', '');
        return $amount;
    }

    /**
     * Redirect customer to shopping cart and show error message
     *
     * @param string $errorMessage
     * @return void
     */
    private function redirectToCartAndShowError($errorMessage)
    {
        $this->messageManager->addError($errorMessage);
        $this->_redirect('checkout/cart');
    }

    private function getServiceURL()
    {
        if ($this->config->getMode() == Config::MODE_LIVE) {
            return Config::URL_PAYPAL_COMPLETION_LIVE;
        } else {
            return Config::URL_PAYPAL_COMPLETION_TEST;
        }
    }

    private function validatePostDataStatusAndStatusDetail()
    {
        if (empty($this->postData) || !isset($this->postData->Status) || $this->postData->Status != "PAYPALOK") {
            if (!empty($this->postData) && isset($this->postData->StatusDetail)) {
                throw new LocalizedException(__("Can not place PayPal orders: %1", $this->postData->StatusDetail));
            } else {
                throw new LocalizedException(__("Can not place PayPal order, please try another payment method"));
            }
        }
    }

    private function loadQuoteFromDataSource()
    {
        $quoteId = $this->encryptor->decrypt($this->getRequest()->getParam("quoteid"));
        $this->quote = $this->quoteRepository->get($quoteId);

        if (!isset($this->quote) || empty($this->quote->getId())) {
            throw new LocalizedException(__("Unable to find payment data."));
        }
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    private function loadOrderFromDataSource()
    {
        $incrementId = $this->quote->getReservedOrderId();

        $filter = array(
            'field' => 'increment_id',
            'value' => $incrementId,
            'conditionType' => 'eq'
        );

        $searchCriteria = $this->_repositoryQuery
            ->buildSearchCriteriaWithOR(array($filter), 1, 1);

        $orders = $this->orderRepository->getList($searchCriteria);

        $orderCount = $orders->getTotalCount();

        if($orderCount > 0){
            $order = $this->order = current($orders->getItems());
        }

        if ($order === null || $order->getId() === null) {
            throw new LocalizedException(__("Invalid order."));
        }

        return $order;
    }

    /**
     * @param $transactionId
     * @param $payment
     * @param $completionResponse
     * @throws ValidatorException
     */
    private function updatePaymentInformation($transactionId, $payment, $completionResponse)
    {
        $this->suiteLogger->sageLog(Logger::LOG_REQUEST, "Flag TransactionId: "
            . $transactionId, [__METHOD__, __LINE__]);

        $this->suiteLogger->sageLog(Logger::LOG_REQUEST, "Flag getLastTransId: "
            . $payment->getLastTransId(), [__METHOD__, __LINE__]);

        if (!empty($transactionId) && $payment->getLastTransId() == $transactionId) {
            $payment->setAdditionalInformation('statusDetail', $completionResponse['StatusDetail']);
            $payment->setAdditionalInformation('threeDStatus', $completionResponse['3DSecureStatus']);
            $payment->setCcType("PayPal");
            $payment->setLastTransId($transactionId);
            $payment->save();
        } else {
            throw new ValidatorException(__('Invalid transaction id'));
        }
    }
}
