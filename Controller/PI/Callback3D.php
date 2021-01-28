<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Ebizmarts\SagePaySuite\Helper\CustomerLogin;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class Callback3D extends Action
{
    const DUPLICATED_CALLBACK_ERROR_MESSAGE = 'Duplicated 3D security callback received.';
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

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var CustomerSession */
    private $customerSession;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var CustomerLogin */
    private $customerLogin;

    /**
     * Callback3D constructor.
     * @param Context $context
     * @param Config $config
     * @param ThreeDSecureCallbackManagement $requester
     * @param PiRequestManagerFactory $piReqManagerFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param CryptAndCodeData $cryptAndCode
     * @param RecoverCart $recoverCart
     * @param CheckoutSession $checkoutSession
     * @param Logger $suiteLogger
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerLogin $customerLogin
     */
    public function __construct(
        Context $context,
        Config $config,
        ThreeDSecureCallbackManagement $requester,
        PiRequestManagerFactory $piReqManagerFactory,
        OrderRepositoryInterface $orderRepository,
        CryptAndCodeData $cryptAndCode,
        RecoverCart $recoverCart,
        CheckoutSession $checkoutSession,
        Logger $suiteLogger,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        CustomerLogin $customerLogin
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
        $this->customerSession             = $customerSession;
        $this->customerRepository          = $customerRepository;
        $this->customerLogin               = $customerLogin;
    }

    public function execute()
    {
        $encryptedOrderId = $this->getRequest()->getParam("orderId");
        $orderId = $this->decodeAndDecrypt($encryptedOrderId);
        try {
            $sanitizedPares = $this->sanitizePares($this->getRequest()->getPost('PaRes'));
            $order = $this->orderRepository->get($orderId);
            $customerId = $order->getCustomerId();

            if ($customerId != null) {
                $this->customerLogin->logInCustomer($customerId);
            }

            $payment = $order->getPayment();
            if ($this->isParesDuplicated($payment, $sanitizedPares)) {
                $this->javascriptRedirect('checkout/onepage/success');
                return;
            } else {
                $payment->setAdditionalInformation(SagePaySession::PARES_SENT, $sanitizedPares);
                $payment->save();
            }

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
        } catch (ApiException $apiException) {
            $this->recoverCart->setShouldCancelOrder(true)->setOrderId($orderId)->execute();
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $apiException->getTraceAsString(), [__METHOD__, __LINE__]);
            $this->messageManager->addError($apiException->getUserMessage());
            $this->javascriptRedirect('checkout/cart');
        } catch (\RuntimeException $runtimeException) {
            $vpstxid = $this->getRequest()->getParam("transactionId");
            $message = self::DUPLICATED_CALLBACK_ERROR_MESSAGE . ' OrderId: ' . $orderId . ' VPSTxId: ' . $vpstxid;
            $this->suiteLogger->sageLog(Logger::LOG_REQUEST, $message, [__METHOD__, __LINE__]);
            throw new \RuntimeException(__(self::DUPLICATED_CALLBACK_ERROR_MESSAGE));
        } catch (\Exception $e) {
            $this->recoverCart->setShouldCancelOrder(true)->setOrderId($orderId)->execute();
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getTraceAsString(), [__METHOD__, __LINE__]);
            $this->messageManager->addError(__("Something went wrong: %1", $e->getMessage()));
            $this->javascriptRedirect('checkout/cart');
        }
    }

    /**
     * @param $payment
     * @param $pares
     * @return bool
     */
    private function isParesDuplicated($payment, $pares)
    {
        $savedPares = $payment->getAdditionalInformation(SagePaySession::PARES_SENT);
        return ($savedPares !== null) && ($pares === $savedPares);
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
