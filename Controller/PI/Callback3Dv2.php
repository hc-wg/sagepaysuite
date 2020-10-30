<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Ebizmarts\SagePaySuite\Model\ObjectLoader\OrderLoader;

class Callback3Dv2 extends Action
{
    /** @var Config */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    /** @var ThreeDSecureCallbackManagement */
    private $requester;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager */
    private $piRequestManagerDataFactory;

    /** @var Session */
    private $checkoutSession;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var QuoteRepository */
    private $quoteRepository;

    /** @var CryptAndCodeData */
    private $cryptAndCode;

    /** @var RecoverCart */
    private $recoverCart;

    /** @var OrderLoader */
    private $orderLoader;

    /**
     * Callback3Dv2 constructor.
     * @param Context $context
     * @param Config $config
     * @param LoggerInterface $logger
     * @param ThreeDSecureCallbackManagement $requester
     * @param PiRequestManagerFactory $piReqManagerFactory
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param QuoteRepository $quoteRepository
     * @param CryptAndCodeData $cryptAndCode
     * @param RecoverCart $recoverCart
     * @param OrderLoader $orderLoader
     */
    public function __construct(
        Context $context,
        Config $config,
        LoggerInterface $logger,
        ThreeDSecureCallbackManagement $requester,
        PiRequestManagerFactory $piReqManagerFactory,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        QuoteRepository $quoteRepository,
        CryptAndCodeData $cryptAndCode,
        RecoverCart $recoverCart,
        OrderLoader $orderLoader
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->config->setMethodCode(Config::METHOD_PI);
        $this->logger = $logger;
        $this->checkoutSession    = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;

        $this->requester = $requester;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
        $this->cryptAndCode                = $cryptAndCode;
        $this->recoverCart                 = $recoverCart;
        $this->orderLoader                 = $orderLoader;
    }

    public function execute()
    {
        try {
            $quoteIdEncrypted = $this->getRequest()->getParam("quoteId");
            $quoteIdFromParams = $this->cryptAndCode->decodeAndDecrypt($quoteIdEncrypted);
            $quote = $this->quoteRepository->get((int)$quoteIdFromParams);

            $order = $this->orderLoader->loadOrderFromQuote($quote);
            $orderId = $order->getId();

            $payment = $order->getPayment();

            /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager $data */
            $data = $this->piRequestManagerDataFactory->create();
            $data->setTransactionId($payment->getLastTransId());
            $data->setCres($this->getRequest()->getPost('cres'));
            $data->setVendorName($this->config->getVendorname());
            $data->setMode($this->config->getMode());
            $data->setPaymentAction($this->config->getSagepayPaymentAction());

            $this->requester->setRequestData($data);

            $this->setRequestParamsForConfirmPayment($orderId, $order);

            $response = $this->requester->placeOrder();

            if ($response->getErrorMessage() === null) {
                $this->javascriptRedirect('checkout/onepage/success');
            } else {
                $this->messageManager->addError($response->getErrorMessage());
                $this->javascriptRedirect('checkout/cart');
            }
        } catch (ApiException $apiException) {
            $this->recoverCart->setShouldCancelOrder(true)->execute();
            $this->logger->critical($apiException);
            $this->messageManager->addError($apiException->getUserMessage());
            $this->javascriptRedirect('checkout/cart');
        } catch (\Exception $e) {
            $this->recoverCart->setShouldCancelOrder(true)->execute();
            $this->logger->critical($e);
            $this->messageManager->addError(__("Something went wrong: %1", $e->getMessage()));
            $this->javascriptRedirect('checkout/cart');
        }
    }

    private function javascriptRedirect($url)
    {
        //redirect to success via javascript
        $this
            ->getResponse()
            ->setBody(
                '<script>window.top.location.href = "'
                . $this->_url->getUrl($url, ['_secure' => true])
                . '";</script>'
            );
    }

    /**
     * @param int $orderId
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    private function setRequestParamsForConfirmPayment(int $orderId, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        $orderId = $this->encryptAndEncode((string)$orderId);
        $quoteId = $this->encryptAndEncode((string)$order->getQuoteId());

        $this->getRequest()->setParams([
                'orderId' => $orderId,
                'quoteId' => $quoteId
            ]);
    }

    /**
     * @param $data
     * @return string
     */
    public function encryptAndEncode($data)
    {
        return $this->cryptAndCode->encryptAndEncode($data);
    }
}
