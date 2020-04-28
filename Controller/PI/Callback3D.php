<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Magento\Checkout\Model\Session;
use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

class Callback3D extends Action
{
    /** @var Config */
    private $config;

    private $suiteLogger;

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

    /** @var Session */
    private $checkoutSession;

    /**
     * Callback3D constructor.
     * @param Context $context
     * @param Config $config
     * @param ThreeDSecureCallbackManagement $requester
     * @param PiRequestManagerFactory $piReqManagerFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param CryptAndCodeData $cryptAndCode
     * @param RecoverCart $recoverCart
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        Config $config,
        ThreeDSecureCallbackManagement $requester,
        PiRequestManagerFactory $piReqManagerFactory,
        OrderRepositoryInterface $orderRepository,
        CryptAndCodeData $cryptAndCode,
        RecoverCart $recoverCart,
        Session $checkoutSession,
        Logger $suiteLogger
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->config->setMethodCode(Config::METHOD_PI);
        $this->orderRepository             = $orderRepository;
        $this->requester                   = $requester;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
        $this->cryptAndCode                = $cryptAndCode;
        $this->recoverCart                 = $recoverCart;
        $this->checkoutSession             = $checkoutSession;
        $this->suiteLogger                 = $suiteLogger;
    }

    public function execute()
    {
        try {
            $sanitizedPares = $this->sanitizePares($this->getRequest()->getPost('PaRes'));
            if(!$this->isParesDuplicated($sanitizedPares)) {
                $this->suiteLogger->sageLog(Logger::LOG_REQUEST, $this->getRequest()->getPost(), [__METHOD__, __LINE__]);
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

                $data->setParEs($sanitizedPares);
                $data->setVendorName($this->config->getVendorname());
                $data->setMode($this->config->getMode());
                $data->setPaymentAction($this->config->getSagepayPaymentAction());

                $this->checkoutSession->setData(SagePaySession::PARES_SENT, $sanitizedPares);

                $this->requester->setRequestData($data);

                $response = $this->requester->placeOrder();

                if ($response->getErrorMessage() === null) {
                    $this->javascriptRedirect('checkout/onepage/success');
                } else {
                    $this->messageManager->addError($response->getErrorMessage());
                    $this->javascriptRedirect('checkout/cart');
                }
            } else {
                throw new \RuntimeException('Duplicated POST request received');
            }
        } catch (ApiException $apiException) {
            $this->recoverCart->setShouldCancelOrder(true)->execute();
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $apiException->getTraceAsString(), [__METHOD__, __LINE__]);
            $this->messageManager->addError($apiException->getUserMessage());
            $this->javascriptRedirect('checkout/cart');
        } catch (\Exception $e) {
            $this->recoverCart->setShouldCancelOrder(true)->execute();
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getTraceAsString(), [__METHOD__, __LINE__]);
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

    /**
     * @param $pares
     * @return bool
     */
    private function isParesDuplicated($pares)
    {
        $sessionPares = $this->checkoutSession->getData(SagePaySession::PARES_SENT);
        return ($sessionPares !== null) && ($pares === $sessionPares);
    }
}
