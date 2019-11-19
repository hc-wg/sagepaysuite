<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement;
use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ebizmarts\SagePaySuite\Model\RecoverCartAndCancelOrder;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;

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

    /** @var RecoverCartAndCancelOrder */
    private $recoverCartAndCancelOrder;

    /** @var CryptAndCodeData */
    private $cryptAndCode;

    /**
     * Callback3Dv2 constructor.
     * @param Context $context
     * @param Config $config
     * @param LoggerInterface $logger
     * @param ThreeDSecureCallbackManagement $requester
     * @param PiRequestManagerFactory $piReqManagerFactory
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param RecoverCartAndCancelOrder $recoverCartAndCancelOrder
     * @param CryptAndCodeData $cryptAndCode
     */
    public function __construct(
        Context $context,
        Config $config,
        LoggerInterface $logger,
        ThreeDSecureCallbackManagement $requester,
        PiRequestManagerFactory $piReqManagerFactory,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        RecoverCartAndCancelOrder $recoverCartAndCancelOrder,
        CryptAndCodeData $cryptAndCode
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->config->setMethodCode(Config::METHOD_PI);
        $this->logger                      = $logger;
        $this->checkoutSession             = $checkoutSession;
        $this->orderRepository             = $orderRepository;
        $this->requester                   = $requester;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
        $this->recoverCartAndCancelOrder   = $recoverCartAndCancelOrder;
        $this->cryptAndCode                = $cryptAndCode;
    }

    public function execute()
    {
        try {
            $orderId = (int)$this->checkoutSession->getData(SagePaySession::PRESAVED_PENDING_ORDER_KEY);
            $order = $this->orderRepository->get($orderId);

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
            $this->recoverCartAndCancelOrder->execute();
            $this->logger->critical($apiException);
            $this->messageManager->addError($apiException->getUserMessage());
            $this->javascriptRedirect('checkout/cart');
        } catch (\Exception $e) {
            $this->recoverCartAndCancelOrder->execute();
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
        $orderId = $this->cryptAndCode->encryptAndEncode((string)$orderId);
        $quoteId = $this->cryptAndCode->encryptAndEncode((string)$order->getQuoteId());
        $this->getRequest()->setParams([
                'orderId' => $orderId,
                'quoteId' => $quoteId
            ]);
    }
}
