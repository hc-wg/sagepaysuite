<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory;
use Ebizmarts\SagePaySuite\Helper\CustomerLogin;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement;
use Ebizmarts\SagePaySuite\Model\Session as SagePaySession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;

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

    /** @var CheckoutSession */
    private $checkoutSession;

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
     * @param CheckoutSession $checkoutSession
     * @param Logger $suiteLogger
     * @param CustomerLogin $customerLogin
     */
    public function __construct(
        Context $context,
        Config $config,
        ThreeDSecureCallbackManagement $requester,
        PiRequestManagerFactory $piReqManagerFactory,
        OrderRepositoryInterface $orderRepository,
        CryptAndCodeData $cryptAndCode,
        CheckoutSession $checkoutSession,
        Logger $suiteLogger,
        CustomerLogin $customerLogin
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->config->setMethodCode(Config::METHOD_PI);
        $this->orderRepository             = $orderRepository;
        $this->requester                   = $requester;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
        $this->cryptAndCode                = $cryptAndCode;
        $this->checkoutSession             = $checkoutSession;
        $this->suiteLogger                 = $suiteLogger;
        $this->customerLogin               = $customerLogin;
    }

    public function execute()
    {
        $encryptedOrderId = $this->getRequest()->getParam("orderId");
        $orderId = $this->decodeAndDecrypt($encryptedOrderId);
        $encryptedQuoteId = $this->getRequest()->getParam("quoteId");
        $quoteId = $this->decodeAndDecrypt($encryptedQuoteId);
        try {
            $sanitizedPares = $this->sanitizePares($this->getRequest()->getPost('PaRes'));
            $order = $this->orderRepository->get($orderId);
            $customerId = $order->getCustomerId();
            $this->suiteLogger->debugLog($order->getData(), [__LINE__, __METHOD__]);

            if ($customerId != null) {
                $this->customerLogin->logInCustomer($customerId);
            }

            $payment = $order->getPayment();
            $this->suiteLogger->debugLog($payment->getData(), [__LINE__, __METHOD__]);

            if ($this->isParesDuplicated($payment, $sanitizedPares)) {
                $this->suiteLogger->debugLog("Duplicated Pares", [__LINE__, __METHOD__]);
                $this->javascriptRedirect('sagepaysuite/pi/success', $order->getQuoteId(), $orderId);
                return;
            } else {
                $payment->setAdditionalInformation(SagePaySession::PARES_SENT, $sanitizedPares);
                $payment->save();
            }

            $orderState = $order->getState();
            $this->suiteLogger->debugLog("Order State: " . $orderState, [__LINE__, __METHOD__]);
            if ($orderState !== \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
                $this->javascriptRedirect('sagepaysuite/pi/success', $order->getQuoteId(), $orderId);
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

            $this->suiteLogger->orderEndLog($order->getIncrementId(), $order->getQuoteId(), $payment->getLastTransId());
            if ($response->getErrorMessage() === null) {
                $this->javascriptRedirect('sagepaysuite/pi/success', $order->getQuoteId(), $orderId);
            } else {
                $this->javascriptRedirect('sagepaysuite/pi/failure', $quoteId, null, $response->getErrorMessage());
            }
        } catch (ApiException $apiException) {
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $apiException->getMessage() . " - orderId: " . $orderId, [__METHOD__, __LINE__]);
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $apiException->getTraceAsString(), [__METHOD__, __LINE__]);
            $this->javascriptRedirect('sagepaysuite/pi/failure', $quoteId, $orderId, $apiException->getUserMessage());
        } catch (\Exception $e) {
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getMessage() . " - orderId: " . $orderId, [__METHOD__, __LINE__]);
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getTraceAsString(), [__METHOD__, __LINE__]);
            $this->javascriptRedirect('sagepaysuite/pi/failure', $quoteId, $orderId, $e->getMessage());
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

    /**
     * @param $url
     * @param $quoteId
     * @param $orderId
     * @param $errorMessage
     */
    private function javascriptRedirect($url, $quoteId = null, $orderId = null, $errorMessage = null)
    {
        $finalUrl = $this->_url->getUrl($url, ['_secure' => true]);
        if ($quoteId !== null) {
            $finalUrl .= "?quoteId=$quoteId";
        }

        if ($orderId !== null) {
            if ($quoteId !== null) {
                $finalUrl .= "&orderId=$orderId";
            } else {
                $finalUrl .= "?orderId=$orderId";
            }
        }

        if ($errorMessage !== null) {
            $errorMessage = urlencode($errorMessage);
            if ($quoteId !== null || $orderId !== null) {
                $finalUrl .= "&errorMessage=$errorMessage";
            } else {
                $finalUrl .= "?errorMessage=$errorMessage";
            }
        }

        //redirect to success via javascript
        $this
            ->getResponse()
            ->setBody(
                '<script>window.top.location.href = "'
                . $finalUrl
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
