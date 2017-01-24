<?php

namespace Ebizmarts\SagePaySuite\Model;

use Ebizmarts\SagePaySuite;

class PiRequestManagement implements \Ebizmarts\SagePaySuite\Api\PiManagementInterface
{
    /** @var Config */
    private $_config;

    /** @var SagePaySuite\Helper\Data */
    private $_suiteHelper;

    /** @var \Magento\Quote\Model\Quote */
    private $_quote;

    /** @var Logger\Logger */
    private $_suiteLogger;

    /** @var \Psr\Log\LoggerInterface */
    private $_logger;

    /** @var Api\PIRest */
    private $_pirestapi;

    /** @var SagePaySuite\Helper\Checkout */
    private $_checkoutHelper;

    /** @var array */
    private $_postData;

    /** @var \Magento\Customer\Model\Session */
    private $_customerSession;

    /** @var \Magento\Checkout\Model\Session */
    private $_checkoutSession;

    /** @var \Ebizmarts\SagePaySuite\Helper\Request */
    private $_requestHelper;

    /** @var \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType */
    private $ccConverter;

    /** @var \Ebizmarts\SagePaySuite\Model\PiRequest */
    private $piRequest;

    /** @var \Ebizmarts\SagePaySuite\Api\Data\ResultInterface $result */
    private $result;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    private $quoteRepository;

    /**
     * PiRequestManagement constructor.
     * @param Config $config
     * @param SagePaySuite\Helper\Data $suiteHelper
     * @param Logger\Logger $suiteLogger
     * @param Api\PIRest $pirestapi
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param SagePaySuite\Helper\Checkout $checkoutHelper
     * @param SagePaySuite\Helper\Request $requestHelper
     * @param Config\SagePayCardType $ccConvert
     * @param PiRequest $piRequest
     * @param SagePaySuite\Api\Data\ResultInterface $result
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Config $config,
        \Ebizmarts\SagePaySuite\Helper\Data $suiteHelper,
        \Ebizmarts\SagePaySuite\Model\Logger\Logger $suiteLogger,
        \Ebizmarts\SagePaySuite\Model\Api\PIRest $pirestapi,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\SagePaySuite\Helper\Checkout $checkoutHelper,
        \Ebizmarts\SagePaySuite\Helper\Request $requestHelper,
        \Ebizmarts\SagePaySuite\Model\Config\SagePayCardType $ccConvert,
        \Ebizmarts\SagePaySuite\Model\PiRequest $piRequest,
        \Ebizmarts\SagePaySuite\Api\Data\ResultInterface $result,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->_config          = $config;
        $this->_config->setMethodCode(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
        $this->_suiteHelper     = $suiteHelper;
        $this->_suiteLogger     = $suiteLogger;
        $this->_pirestapi       = $pirestapi;
        $this->_logger          = $logger;
        $this->_checkoutHelper  = $checkoutHelper;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_requestHelper   = $requestHelper;
        $this->_quote           = $this->_checkoutSession->getQuote();
        $this->ccConverter      = $ccConvert;
        $this->piRequest        = $piRequest;
        $this->result           = $result;
        $this->quoteRepository  = $quoteRepository;
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformationAndPlaceOrder($cartId, \Ebizmarts\SagePaySuite\Api\Data\PiRequestInterface $requestData)
    {
        try {
            //prepare quote
            $quote = $this->getQuoteById($cartId);
            $quote->collectTotals();
            $quote->reserveOrderId();
            $vendorTxCode = $this->_suiteHelper->generateVendorTxCode($this->_quote->getReservedOrderId());

            //generate POST request
            $request = $this->piRequest
                ->setCart($this->_quote)
                ->setCardIdentifier($requestData->getCardIdentifier())
                ->setIsMoto(false)
                ->setMerchantSessionKey($requestData->getMerchantSessionKey())
                ->setVendorTxCode($vendorTxCode)
                ->getRequestData();

            //send POST to Sage Pay
            $postResponse = $this->_pirestapi->capture($request);

            $this->_suiteLogger->sageLog('Request', $postResponse, [__METHOD__, __LINE__]);

            if ($postResponse->statusCode == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS ||
                $postResponse->statusCode == \Ebizmarts\SagePaySuite\Model\Config::AUTH3D_REQUIRED_STATUS
            ) {
                //set payment info for save order
                $transactionId = $postResponse->transactionId;
                $payment = $quote->getPayment();
                $payment->setMethod(\Ebizmarts\SagePaySuite\Model\Config::METHOD_PI);
                $payment->setTransactionId($transactionId);
                $payment->setAdditionalInformation('statusCode', $postResponse->statusCode);
                $payment->setAdditionalInformation('statusDetail', $postResponse->statusDetail);
                $payment->setAdditionalInformation('vendorTxCode', $vendorTxCode);
                if (isset($postResponse->{'3DSecure'})) {
                    $payment->setAdditionalInformation('threeDStatus', $postResponse->{'3DSecure'}->status);
                }

                //DropIn
                if (isset($postResponse->paymentMethod)) {
                    if (isset($postResponse->paymentMethod->card)) {
                        $card = $postResponse->paymentMethod->card;
                        $payment->setCcLast4($card->lastFourDigits);
                        $payment->setCcExpMonth(substr($card->expiryDate, 0, 2));
                        $payment->setCcExpYear(substr($card->expiryDate, 2, 2));
                        $payment->setCcType($this->ccConverter->convert($card->cardType));
                    }
                }
                else {
                    //Custom cc form
                    $payment->setCcLast4($requestData->getCcLastFour());
                    $payment->setCcExpMonth($requestData->getCcExpMonth());
                    $payment->setCcExpYear($requestData->getCcExpYear());
                    $payment->setCcType($this->ccConverter->convert($requestData->getCcType()));
                }

                $payment->setAdditionalInformation('vendorname', $this->_config->getVendorname());
                $payment->setAdditionalInformation('mode', $this->_config->getMode());
                $payment->setAdditionalInformation('paymentAction', $this->_config->getSagepayPaymentAction());

                //save order with pending payment
                $order = $this->_checkoutHelper->placeOrder();

                if ($order) {
                    //set pre-saved order flag in checkout session
                    $this->_checkoutSession->setData("sagepaysuite_presaved_order_pending_payment", $order->getId());

                    $payment = $order->getPayment();
                    $payment->setTransactionId($transactionId);
                    $payment->setLastTransId($transactionId);
                    $payment->save();

                    //invoice
                    if ($postResponse->statusCode == \Ebizmarts\SagePaySuite\Model\Config::SUCCESS_STATUS) {
                        $payment->getMethodInstance()->markAsInitialized();
                        $order->place()->save();

                        //send email
                        $this->_checkoutHelper->sendOrderEmail($order);

                        //prepare session to success page
                        $this->_checkoutSession->clearHelperData();
                        //set last successful quote
                        $this->_checkoutSession->setLastQuoteId($quote->getId());
                        $this->_checkoutSession->setLastSuccessQuoteId($quote->getId());
                        $this->_checkoutSession->setLastOrderId($order->getId());
                        $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
                        $this->_checkoutSession->setLastOrderStatus($order->getStatus());
                    }
                } else {
                    throw new \Magento\Framework\Validator\Exception(__('Unable to save Sage Pay order'));
                }

                //additional details required for callback URL
                $postResponse->orderId = $order->getId();
                $postResponse->quoteId = $quote->getId();

                $this->_suiteLogger->sageLog('Request', (array)$postResponse, [__METHOD__, __LINE__]);

                $this->result->setSuccess(true);
                $this->result->setResponse($postResponse);
            } else {
                throw new \Magento\Framework\Validator\Exception(
                    __('Invalid Sage Pay response, please use another payment method.')
                );
            }
        } catch (\Ebizmarts\SagePaySuite\Model\Api\ApiException $apiException) {
            $this->_logger->critical($apiException);
            $this->result->setSuccess(false);
            $this->result->setErrorMessage(__('Something went wrong: ' . $apiException->getUserMessage()));
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->result->setSuccess(false);
            $this->result->setErrorMessage(__('Something went wrong: ' . $e->getMessage()));
        }

        return $this->result;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuoteById($cartId)
    {
        return $this->getQuoteRepository()->get($cartId);
    }

    public function getQuoteRepository()
    {
        return $this->quoteRepository;
    }

    public function getQuoteIdMaskFactory()
    {
        return $this->quoteIdMaskFactory;
    }
}
