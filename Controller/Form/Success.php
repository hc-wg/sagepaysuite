<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Controller\Form;

use Ebizmarts\SagePaySuite\Helper\RepositoryQuery;
use Ebizmarts\SagePaySuite\Model\Form;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\OrderUpdateOnCallback;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\AlreadyExistsException;
use Ebizmarts\SagePaySuite\Helper\Data as SuiteHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\OrderRepository;

class Success extends Action
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote;

    /**
     * @var Session
     */
    private $_checkoutSession;

    /**
     * Logging instance
     * @var \Ebizmarts\SagePaySuite\Model\Logger\Logger
     */
    private $_suiteLogger;

    /**
     * @var Form
     */
    private $_formModel;

    /**
     * @var OrderRepository
     */
    private $_orderRepository;

    /**
     * @var QuoteRepository
     */
    private $_quoteRepository;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /** @var OrderSender */
    private $orderSender;

    /** @var OrderUpdateOnCallback */
    private $updateOrderCallback;

    /**
     * @var SuiteHelper
     */
    private $suiteHelper;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var RepositoryQuery
     */
    private $_repositoryQuery;

    /**
     * Success constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param Logger $suiteLogger
     * @param Form $formModel
     * @param OrderSender $orderSender
     * @param OrderUpdateOnCallback $updateOrderCallback
     * @param SuiteHelper $suiteHelper
     * @param EncryptorInterface $encryptor
     * @param QuoteRepository $quoteRepository
     * @param OrderRepository $orderRepository
     * @param RepositoryQuery $repositoryQuery
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Logger $suiteLogger,
        Form $formModel,
        OrderSender $orderSender,
        OrderUpdateOnCallback $updateOrderCallback,
        SuiteHelper $suiteHelper,
        EncryptorInterface $encryptor,
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        RepositoryQuery $repositoryQuery
    )
    {

        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_suiteLogger = $suiteLogger;
        $this->_formModel = $formModel;
        $this->orderSender = $orderSender;
        $this->updateOrderCallback = $updateOrderCallback;
        $this->suiteHelper = $suiteHelper;
        $this->encryptor = $encryptor;
        $this->_quoteRepository = $quoteRepository;
        $this->_orderRepository = $orderRepository;
        $this->_repositoryQuery = $repositoryQuery;
    }

    /**
     * FORM success callback
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            $response = $this->_formModel->decodeSagePayResponse($this->getRequest()->getParam("crypt"));

            if (!array_key_exists("VPSTxId", $response)) {
                throw new LocalizedException(__('Invalid response from Sage Pay.'));
            }

            $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, $response, [__METHOD__, __LINE__]);
            $quoteIDFromParams = $this->encryptor->decrypt($this->getRequest()->getParam("quoteid"));
            $this->_quote = $this->_quoteRepository->get((int)$quoteIDFromParams);
            $reservedOrderId = $this->_quote->getReservedOrderId();

            $incrementIdFilter = array(
                'field' => 'increment_id',
                'conditionType' => 'eq',
                'value' => $reservedOrderId
            );

            $searchCriteria = $this->_repositoryQuery->buildSearchCriteriaWithOR(array($incrementIdFilter));


            /**
             * @var Order
             */
            $this->_order = null;
            $orders = $this->_orderRepository->getList($searchCriteria);
            $ordersCount = $orders->getTotalCount();
            $orders = $orders->getItems();

            if ($ordersCount > 0) {
                $this->_order = current($orders);
            }

            if ($this->_order === null || $this->_order->getId() === null) {
                throw new LocalizedException(__('Order not available.'));
            }

            $transactionId = $response["VPSTxId"];
            $transactionId = $this->suiteHelper->removeCurlyBraces($transactionId); //strip brackets
            $payment = $this->_order->getPayment();
            $vendorTxCode = $payment->getAdditionalInformation("vendorTxCode");

            if (!empty($transactionId) && ($vendorTxCode == $response['VendorTxCode'])) {
                foreach ($response as $name => $value) {
                    $payment->setTransactionAdditionalInfo($name, $value);
                    $payment->setAdditionalInformation($name, $value);
                }

                $payment->setLastTransId($transactionId);
                $payment->setAdditionalInformation('statusDetail', $response['StatusDetail']);
                $payment->setCcType($response['CardType']);
                $payment->setCcLast4($response['Last4Digits']);

                if (array_key_exists("ExpiryDate", $response)) {
                    $payment->setCcExpMonth(substr($response["ExpiryDate"], 0, 2));
                    $payment->setCcExpYear(substr($response["ExpiryDate"], 2));
                }
                if (array_key_exists("3DSecureStatus", $response)) {
                    $payment->setAdditionalInformation('threeDStatus', $response["3DSecureStatus"]);
                }
                $payment->save();
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Invalid transaction id.'));
            }

            $redirect = 'sagepaysuite/form/failure';
            $status = $response['Status'];

            if ($status == "OK" || $status == "AUTHENTICATED" || $status == "REGISTERED") {
                $this->updateOrderCallback->setOrder($this->_order);

                try {
                    $this->updateOrderCallback->confirmPayment($transactionId);
                } catch (AlreadyExistsException $ex) {
                    $this->_suiteLogger->sageLog(Logger::LOG_REQUEST, "Sage Pay retry. $transactionId", [__METHOD__, __LINE__]);
                }
                $redirect = 'checkout/onepage/success';
            } elseif ($status == "PENDING") {
                //Transaction in PENDING state (this is just for Euro Payments)
                $payment->setAdditionalInformation('euroPayment', true);

                //send order email
                $this->orderSender->send($this->_order);

                $redirect = 'checkout/onepage/success';
            }

            //prepare session to success page
            $this->_checkoutSession->start();
            $this->_checkoutSession->clearHelperData();
            $this->_checkoutSession->setLastQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastSuccessQuoteId($this->_quote->getId());
            $this->_checkoutSession->setLastOrderId($this->_order->getId());
            $this->_checkoutSession->setLastRealOrderId($this->_order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($this->_order->getStatus());
            $this->_checkoutSession->setData(\Ebizmarts\SagePaySuite\Model\Session::PRESAVED_PENDING_ORDER_KEY, null);

            return $this->_redirect($redirect);
        } catch (\Exception $e) {
            $this->_suiteLogger->logException($e);
            $this->_redirectToCartAndShowError(
                __('Your payment was successful but the order was NOT created, please contact us: %1', $e->getMessage())
            );
        }
    }

    /**
     * Redirect customer to shopping cart and show error message
     *
     * @param string $errorMessage
     * @return void
     */
    private function _redirectToCartAndShowError($errorMessage)
    {
        $this->messageManager->addError($errorMessage);
        $this->_redirect('checkout/cart');
    }
}
