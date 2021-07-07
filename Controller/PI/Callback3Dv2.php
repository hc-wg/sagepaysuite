<?php

namespace Ebizmarts\SagePaySuite\Controller\PI;

use Ebizmarts\SagePaySuite\Api\Data\PiRequestManagerFactory;
use Ebizmarts\SagePaySuite\Helper\CustomerLogin;
use Ebizmarts\SagePaySuite\Model\Api\ApiException;
use Ebizmarts\SagePaySuite\Model\Config;
use Ebizmarts\SagePaySuite\Model\CryptAndCodeData;
use Ebizmarts\SagePaySuite\Model\Logger\Logger;
use Ebizmarts\SagePaySuite\Model\ObjectLoader\OrderLoader;
use Ebizmarts\SagePaySuite\Model\PiRequestManagement\ThreeDSecureCallbackManagement;
use Ebizmarts\SagePaySuite\Model\RecoverCart;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\OrderRepositoryInterface;

class Callback3Dv2 extends Action
{
    /** @var Config */
    private $config;

    /** @var ThreeDSecureCallbackManagement */
    private $requester;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\PiRequestManager */
    private $piRequestManagerDataFactory;

    /** @var QuoteRepository */
    private $quoteRepository;

    /** @var CryptAndCodeData */
    private $cryptAndCode;

    /** @var OrderLoader */
    private $orderLoader;

    /** @var CustomerSession */
    private $customerSession;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var CustomerLogin */
    private $customerLogin;

    /** @var Logger */
    private $suiteLogger;

    /**
     * Callback3D constructor.
     * @param Context $context
     * @param Config $config
     * @param ThreeDSecureCallbackManagement $requester
     * @param PiRequestManagerFactory $piReqManagerFactory
     * @param QuoteRepository $quoteRepository
     * @param CryptAndCodeData $cryptAndCode
     * @param OrderLoader $orderLoader
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerLogin $customerLogin
     * @param Logger $suiteLogger
     */
    public function __construct(
        Context $context,
        Config $config,
        ThreeDSecureCallbackManagement $requester,
        PiRequestManagerFactory $piReqManagerFactory,
        QuoteRepository $quoteRepository,
        CryptAndCodeData $cryptAndCode,
        OrderLoader $orderLoader,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        CustomerLogin $customerLogin,
        Logger $suiteLogger
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->config->setMethodCode(Config::METHOD_PI);
        $this->quoteRepository             = $quoteRepository;
        $this->quoteRepository             = $quoteRepository;
        $this->requester                   = $requester;
        $this->piRequestManagerDataFactory = $piReqManagerFactory;
        $this->cryptAndCode                = $cryptAndCode;
        $this->orderLoader                 = $orderLoader;
        $this->customerSession             = $customerSession;
        $this->customerRepository          = $customerRepository;
        $this->customerLogin               = $customerLogin;
        $this->suiteLogger                 = $suiteLogger;
    }

    public function execute()
    {
        $orderId = null;
        $quoteIdEncrypted = $this->getRequest()->getParam("quoteId");
        $quoteIdFromParams = $this->cryptAndCode->decodeAndDecrypt($quoteIdEncrypted);
        try {
            $quote = $this->quoteRepository->get((int)$quoteIdFromParams);

            $order = $this->orderLoader->loadOrderFromQuote($quote);
            $orderId = (int)$order->getId();
            $customerId = $order->getCustomerId();
            $this->suiteLogger->debugLog(
                "OrderId: " . $orderId . " QuoteId: " . $quoteIdFromParams . " CustomerId: " . $customerId,
                [__LINE__, __METHOD__]
            );
            $this->suiteLogger->debugLog($order->getData(), [__LINE__, __METHOD__]);

            if ($customerId != null) {
                $this->customerLogin->logInCustomer($customerId);
            }

            $payment = $order->getPayment();
            $this->suiteLogger->debugLog($payment->getData(), [__LINE__, __METHOD__]);

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

            $this->suiteLogger->orderEndLog($order->getIncrementId(), $quoteIdFromParams, $payment->getLastTransId());
            if ($response->getErrorMessage() === null) {
                $this->javascriptRedirect('sagepaysuite/pi/success', $quote->getId(), $orderId);
            } else {
                $this->javascriptRedirect('sagepaysuite/pi/failure', $quoteIdFromParams, null, $response->getErrorMessage());
            }
        } catch (ApiException $apiException) {
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $apiException->getMessage() . " - orderId: " . $orderId, [__METHOD__, __LINE__]);
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $apiException->getTraceAsString(), [__METHOD__, __LINE__]);
            $this->javascriptRedirect('sagepaysuite/pi/failure', $quoteIdFromParams, $orderId, $apiException->getUserMessage());
        } catch (\Exception $e) {
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getMessage() . " - orderId: " . $orderId, [__METHOD__, __LINE__]);
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getTraceAsString(), [__METHOD__, __LINE__]);
            $this->javascriptRedirect('sagepaysuite/pi/failure', $quoteIdFromParams, $orderId, $e->getMessage());
        }
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

    /**
     * @param $customerId
     */
    public function logInCustomer($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $this->customerSession->setCustomerDataAsLoggedIn($customer);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->suiteLogger->sageLog(Logger::LOG_EXCEPTION, $e->getTraceAsString(), [__METHOD__, __LINE__]);
        }
    }
}
