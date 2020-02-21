<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;
use Ebizmarts\SagePaySuite\Model\RecoverCart;

class Callback3D extends Action
{
    /** @var Config */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    /** @var ThreeDSecureCallbackManagement */
    private $requester;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager */
    private $piRequestManagerDataFactory;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var CryptAndCodeData */
    private $cryptAndCode;

    /** @var RecoverCart */
    private $recoverCart;

    /**
     * Callback3D constructor.
     * @param Context $context
     * @param Config $config
     * @param LoggerInterface $logger
     * @param ThreeDSecureCallbackManagement $requester
     * @param PiRequestManagerFactory $piReqManagerFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param CryptAndCodeData $cryptAndCode
     * @param RecoverCart $recoverCart
     */
    public function __construct(
        Context $context,
        Config $config,
        LoggerInterface $logger,
        ThreeDSecureCallbackManagement $requester,
        PiRequestManagerFactory $piReqManagerFactory,
        OrderRepositoryInterface $orderRepository,
        CryptAndCodeData $cryptAndCode,
        RecoverCart $recoverCart
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->config->setMethodCode(Config::METHOD_PI);
        $this->logger                      = $logger;
        $this->orderRepository             = $orderRepository;
        $this->requester                   = $requester;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
        $this->cryptAndCode                = $cryptAndCode;
        $this->recoverCart                 = $recoverCart;
    }

    public function execute()
    {
        try {
            $orderId = $this->getRequest()->getParam("orderId");
            $orderId = $this->decodeAndDecrypt($orderId);
            $order = $this->orderRepository->get($orderId);
            if ($order->getState() !== \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
                $this->javascriptRedirect('checkout/onepage/success');
                return;
            }
            /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager $data */
            $data = $this->piRequestManagerDataFactory->create();
            $data->setTransactionId($this->getRequest()->getParam("transactionId"));
            $sanitizedPares = $this->sanitizePares($this->getRequest()->getPost('PaRes'));
            $data->setParEs($sanitizedPares);
            $data->setVendorName($this->config->getVendorname());
            $data->setMode($this->config->getMode());
            $data->setPaymentAction($this->config->getSagepayPaymentAction());

            $this->requester->setRequestData($data);

            $response = $this->requester->placeOrder();

            if ($response->getErrorMessage() === null) {
                $this->javascriptRedirect('checkout/onepage/success');
            } else {
                $this->messageManager->addError($response->getErrorMessage());
                $this->javascriptRedirect('checkout/cart');
            }
        } catch (ApiException $apiException) {
            $this->recoverCart->execute(true);
            $this->logger->critical($apiException);
            $this->messageManager->addError($apiException->getUserMessage());
            $this->javascriptRedirect('checkout/cart');
        } catch (\Exception $e) {
            $this->recoverCart->execute(true);
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
     * @param $pares
     * @return string
     */
    public function sanitizePares($pares)
    {
        return preg_replace("/[\n\s]/", "", $pares);
    }

    /**
     * @param $data
     * @return string
     */
    public function decodeAndDecrypt($data)
    {
        return $this->cryptAndCode->decodeAndDecrypt($data);
    }
}
